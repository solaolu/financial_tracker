<?php
// Dummy reset using secret code
if ($_POST['code'] === '1234') { echo 'Password reset allowed'; } ?>
<form method='post'><input name='code'><button>Reset</button></form>