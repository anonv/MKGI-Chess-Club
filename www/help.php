<?php

require("mcc_common.php");

$current_player = mcc_check_login();

$html_body = "";

$html_body .= <<<EOT

<div style="width: 500px; text-align: justify;">
<h2>Help Topics</h2>

<p>
<a name="top" href="#rules">A Word On The Chess Rules</a>
</p>
<p>
<a name="top" href="#overview">The Main Section (Overview)</a>
</p>
<ul>
  <li><a href="#mygames">My Games</a></li>
  <li><a href="#search">Search</a></li>
  <li><a href="#rankings">Rankings</a></li>
  <li><a href="#pgnviewer">PGN Archives</a></li>
  <li><a href="#logout">Logout</a></li>
</ul>
<p>
<a name="top" href="#chessboard">The Chessboard</a>
</p>
<ul>
  <li><a href="#move">How To Make Moves</a></li>
  <li><a href="#special">Special Moves And Commands</a></li>
  <li><a href="#undo">How To Undo Moves</a></li>
  <li><a href="#notes">Personal Notes</a></li>
  <li><a href="#browser">History Browser</a></li>
  <li><a href="#pgn">PGN Format</a></li>
</ul>

<a name="rules"><strong>A Word On The Chess Rules</strong></a>
<p>
While I assume that you know the very basic rules of movement,
it is possible that you are not fully aware of the special rules
castling and en-passant.
</p>
<p>
Castling is allowed when neither king nor rook has moved and
the way between them is clear. Further on, <strong>the tile, the king
is on, the tile, the king passes and the tile, where the king will
halt</strong>, must not be under attack by an enemy chessman. If these
conditions are fulfilled the king will either be moved two tiles to
the left, while the left rook will be placed to his right or he 
will be moved two tiles to the right, while the right rook will be
placed to his left.
</p>
<p>
A pawn may move two tiles up or down when he has not moved before.
However, if then an enemy pawn is next to him on the same line, this
enemy pawn may take your pawn just as if he moved up/down only
one tile instead of two. Therefore it moves diagonal behind your
pawn, although the tile is empty and your pawn is taken from the 
board. Note, that this requires a manual modification of the move
command as described in section <a href="#move">How To Make Moves</a>.
En-passant is only possible for the very move after the pawn's twostep.
</p>
<p>These special rules are implemented as well as the recognition
of check, stalemate and checkmate but 
other special rules like the fifty move rule are not implemented.
</p>

<a name="overview"><strong>The Main Section (Overview)</strong></a>
<p>After you successfully logged in, you are in the main
section, also called <cite>Overview</cite>. Here you can find
a list of all your open games, some useful links and a
form to challenge other users.<br />
For each game, there is a red or green light at the left-hand
side of the game description. The green light indicates
that it is your turn, while the red one means the opposite.
You may enter a chess board by clicking <cite>Enter</cite> at the
right-hand side of the description.
</p>

<a name="mygames"><strong>My Games</strong></a>
<p>Return to the initial state of the main section, 
which is to display your open games as described above. 
This is useful after a search.</p>

<a name="search"><strong>Search</strong></a>
<p>Here you can search games from the archive or games of
other players. Therefore you can specify the location (either
archive or open games), the player, which color the player had 
and against which other user he/she played. The results will
be displayed in the main section. To start a new search, simply
follow the link again. To return to the list of your games, use
the <cite>My Games</cite> link.</p>

<a name="rankings"><strong>Rankings</strong></a>
<p>The rating formula for MCC is basically the one created by Arpad
Elo.</p>
<p>You will appear in the rankings after you have completed three
games.
</p>

<a name="pgnviewer"><strong>PGN Archives</strong></a>
<p>
Here you can find chess games from PGN archives, made available by the 
admin. <br /><strong>To the admin:</strong> How PGN archives are added, is explained
in the README.
</p>

<a name="logout"><strong>Logout</strong></a>
<p>This option will always be present as the most right-hand
side link in the link bar. It logs you out from MCC and returns
you to the login screen.</p>

<a name="chessboard"><strong>The Chessboard</strong></a>
<p>When you have selected a game, it is displayed on the
chessboard. To the left-hand side there is a lot of information
like who is playing with whom, what was the last move and the
complete move history as well as the move submission form, given
that you participate in the game. On the right-hand side of the
chessboard the <strong>imbalance</strong> of chessmen is displayed.</p>

<a name="move"><strong>How To Make Moves</strong></a>
<p>
Initially the moves were entered manually. This still works but
it is way more comfortable to assemble the command by clicking
on the chessboard. However, a number of special commands must 
still be entered manually, but more on that later.</p>
<p> To move a chessman, click it first. This will set the first 
part of the command, something like <strong>Pe2</strong> or <strong>Nf3</strong>.
The identifiers are <strong>P</strong> for Pawn, <strong>N</strong> for Knight,
<strong>B</strong> for Bishop, <strong>R</strong> for Rook, <strong>Q</strong> for Queen and
finally <strong>K</strong> for King.</p>
<p>Second, click the target which is either an empty tile or one
occupied by an enemy chessman. This will complete the text command.
If the tile is empty, it is something like <strong>Pe2-e4</strong> and if it
is occupied, it looks like <strong>Nf3xh4</strong>. Thus <strong>-</strong> indicates 
movement, while <strong>x</strong> indicates capturing.</p>
<p>Third, you can enter some funny comment on your move or some 
chatter and finally submit the move by hitting the submit button.
Whether your move is correct, will be checked after submission.
In case of any errors, an error message will be displayed and the
move command will not be executed.
</p>

<a name="special"><strong>Special Moves And Commands</strong></a>
<p>
Castling is done by clicking the king and then
two tiles away on the target tile either to his left or right.
And that is all! If you additionally try to move the rook, you
will <strong>only</strong> move the rook. 
</p>
<p>For en-passant as described in the 
<a href="#rules">Word On The Chess Rules</a> you have to click
the tile behind the enemy pawn (the one he just skipped). As this
tile is empty a minus is displayed in the command. You must 
manually replace it with the letter <strong>x</strong> before you submit
the move.</p>
<p><strong>DELETE</strong> allows you to finish a game without influence
on your ranking but only at the very beginning, thus when you
have not moved a chessman yet.</p>
<p>With <strong>resign</strong> you resign and your opponent wins the game.</p>
<p>With <strong>draw?</strong> you offer a draw which your opponent
may either reject or accept. If he/she rejects, it is again 
your turn.</p>

<a name="undo"><strong>How To Undo Moves</strong></a>
<p>When a move has been successfully executed, the board and
game data will be updated and an Undo button occurs. It will
be there for TWENTY minutes! After that there is no way to
undo your move. If your opponent has already moved in the 
meantime, the undo will have no effect. Only the very last move
can be undone.</p>
<p class="warning">If a move finishes a game, it may not be undone! This
is to prevent anyone from tampering with the statistics by
winning over and over again.</p>

<a name="notes"><strong>Personal Notes</strong></a>
<p>The memo box below the comment box allows you to make personal
notes on your game to plan strategies and tactics. These notes are
encrypted and only you can read them. They can not only be entered
when you perform a move but also when you it is your opponent's turn.
Further on, if it is your turn, but you just want to take down a note,
you can easily do so, by hitting 'Move!' with only a note, then no
errors will occur and no move is performed, but the notes are updated.
</p><p>
Your personal notes are separated user-wise, so in case you have
two open games against the same user, the same notes will be displayed
in these games.
</p>

<a name="browser"><strong>History Browser</strong></a>
<p>In this mode you can browse a game from the first to
the last move. If you enter a game from the archive it will
intially be displayed in the <cite>History Browser</cite> while
an open game is displayed in the so-called <cite>Input Mode</cite>.
If you are in <cite>Input Mode</cite> the link at the top of the
chess board will be <cite>History Browser</cite> and vice versa,
thus the link will take you to the mode you are currently not 
in.</p>

<a name="pgn"><strong>PGN Format</strong></a>
<p>This displays the current game in the wide-spread PGN
format. You can copy and paste the text into a file with
the extension .pgn and view it with any chess software like
Fritz. This way you can analyse games, play variants or comment
the quality of certain moves.</p>

</div>
EOT;

echo mcc_template_page($html_body, "help", "Help");

?>
