import MySQLdb
import time
import sys
import os
import re
import threading
import subprocess

# Number of simultaneous analysis to perform. Allows to use the
# full potential of SMP systems.
THREADS = 2

# Max gnuchess thinking time. This is not really the actual max
# duration which can be 3 times more since we start thinking 3 times
# in the GC script.
MAXTIME = 60

# Path to the gnuchess command.
GNUCHESS = 'gnuchess'

# Time limit allowed to this process
# Adapt this to the time interval between launches to avoid having
# several instances running simultaneously.
# TIMELIMIT = time.time() + 45 * 60   # 45 minutes allowed, used for hourly batch
# TIMELIMIT = time.time() + 23 * 3600 # 23 hours allowed, for daily batch
# TIMELIMIT = None                    # No time limit
TIMELIMIT = None

# DEBUG is activated if a DEBUG file is present. Moves will be dumped in files
# and database updates will be cancelled.
DEBUG = os.path.exists('DEBUG')

# ===================

re_mymovegc  = re.compile("\nmy move is *: ([a-zA-Z0-9_-]+)", re.IGNORECASE)
re_scoregc   = re.compile("score = ([0-9-]*)", re.IGNORECASE)

IGNORE_MOVES = ['draw', 'positional', 'resigned']



class WatchDog ( threading.Thread ):
	
	def __init__ ( self, p_process ):
		threading.Thread.__init__(self)
		self.setDaemon(True)
		self.__process = p_process

	def cancel ( self ):
		self.__active = False

	def run ( self ):
		mythread = threading.currentThread()
		# print "%s == WatchDog Start" % ( mythread.getName() )
		self.__active = True

		while self.__active and self.__process.returncode is None:
			time.sleep(MAXTIME)

			if self.__active and self.__process.returncode is None:
				print "%s == Sending SIGINT" % ( mythread.getName() )
				os.kill(self.__process.pid, 2)

		# print "%s == WatchDog End" % ( mythread.getName() )
		


def AnalyzeLastMove ( moves, depth = 6 ):
	if moves[-1] in IGNORE_MOVES:
		return [0,0,0,0,0], '', 'Move ignored'

	st_variation = "^%2d\.\s+(.+)$" % depth
	re_variation = re.compile(st_variation, re.MULTILINE)

	gc_script = ""
	gc_script += "easy\n"
	gc_script += "depth %d\n" % depth
	gc_script += "force\n"

	for m in moves[:-1]:
		gc_script += "%s\n" % m

	gc_script += "show score\n"
	gc_script += "post\n"
	gc_script += "go\n"
	gc_script += "nopost\n"
	gc_script += "show score\n"
	gc_script += "go\n"
	gc_script += "show score\n"
	gc_script += "undo\n"
	gc_script += "undo\n"
	gc_script += "force\n"
	gc_script += "%s\n" % moves[-1]
	gc_script += "show score\n"
	gc_script += "go\n"
	gc_script += "show score\n"
	gc_script += "exit\n"

	p = subprocess.Popen(GNUCHESS, shell=True, stdin = subprocess.PIPE,
				stdout = subprocess.PIPE)

	p.stdin.write(gc_script)
	p.stdin.flush()

	# Start a watchdog
	# Gnu Chess will end its search on receiving a signal SIGINT

	watchdog = WatchDog(p)
	watchdog.start()
	gc_result = p.stdout.read().replace('\r','\n')
	watchdog.cancel()
	p.wait()

	scores = [ int(s) for s in re_scoregc.findall(gc_result) ]

	# for i, s in enumerate(scores):
	# 	print "SC[%d] = %d" % ( i, s)

	#scores[1] = -scores[1]
	#scores[2] = -scores[2]

	progresslines = re_variation.findall(gc_result)

	# for l in progresslines:
	# 	print "PL: " + l

	if len(progresslines) > 0:
		gcmove = progresslines[-1].split()[3:]
	else:
		gcmove = [ re_mymovegc.findall(gc_result)[0] ]
	

	p.stdin.close()
	p.stdout.close()

	log = ''
	log += "===============================================================================\n"
	log += gc_script + "\n"
	log += "-------------------------------------------------------------------------------\n"
	log += gc_result + "\n"
	log += "-------------------------------------------------------------------------------\n"
	log += "gcmove: %s\n" % gcmove
	log += "===============================================================================\n"
	
	return scores, gcmove, log


def AnalyzeGame ( cursor, cursor_lock, gameid, depth = 6 ):
	mythread = threading.currentThread()

	print "%s == Game %d == Begin" % ( mythread.getName(), gameid )
	
	q = ("select mv_id, mv_short, mv_score"
	   + " from mcc_move"
	   + " where mv_game = %d" % gameid
	   + " order by mv_id")

	cursor_lock.acquire()
	cursor.execute(q)
	movedata = cursor.fetchall()
	cursor_lock.release()

	moves = [ m[1] for m in movedata ]

	for i in range(len(moves)):
		if movedata[i][2] is not None:
			# Ignore moves that are already computed
			continue

		if TIMELIMIT is not None and time.time() > TIMELIMIT:
			# Ignore all moves, we are late...
			continue

		t_pre = time.time()
		scores, gcmove, log = AnalyzeLastMove(moves[:1+i], depth)
		gcscore = scores[2] - scores[0]
		plscore = scores[4] - scores[0]
		t_post = time.time()

		if DEBUG:
			open('%08d.txt' % movedata[i][0], 'w').write(log)

		print "%s %8d %4d %-7s  %4d %-7s %+d (%ds)" 	\
			% (mythread.getName(),
			   movedata[i][0],
			   plscore, moves[i],
			   gcscore, gcmove,
			   plscore - gcscore,
		           int(t_post - t_pre))

		cursor_lock.acquire()
		cursor.execute("begin");
		cursor.execute("update mcc_move"
			+ " set mv_teachermove = '%s'," % MySQLdb.escape_string(' '.join(gcmove))
			+ "     mv_teacherrate = %d," % (plscore - gcscore)
			+ "     mv_score = %d" % -scores[1]
			+ " where mv_id = %d" % movedata[i][0]
			)

		if DEBUG:
			cursor.execute("rollback");
		else:
			cursor.execute("commit");

		cursor_lock.release()

	print "%s == Game %d == End" % ( mythread.getName(), gameid )

if __name__ == '__main__':
	cnx = MySQLdb.connect(host=sys.argv[1], user=sys.argv[2],
			      passwd=sys.argv[3], db=sys.argv[4])

	cursor = cnx.cursor()
	cursor_lock = threading.Lock()

	cursor.execute("select distinct mv_game"
		    + " from mcc_move"
		    + " where mv_score is null"
		    + " order by mv_game desc")

	games = [ m[0] for m in cursor.fetchall() ]

	threads = []

	for gameid in games:
		an_thr = threading.Thread(target=AnalyzeGame, 
				args=(cursor, cursor_lock, gameid, 7))

		while len(threads) == THREADS:
			for th in threads:
				if not th.isAlive():
					threads.remove(th)

			if len(threads) == THREADS:
				time.sleep(0.2)

		if TIMELIMIT is None or time.time() < TIMELIMIT:
			threads.append(an_thr)
			an_thr.start()

