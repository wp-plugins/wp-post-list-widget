Use custom template for the loop or leave unchecked and use the default, which is:
<?php
	$default=$this->getDefaultTemplate();
	echo "Before: <b><pre>".$default["before"];
	echo "</b></pre>Template: <b><pre>".$default["template"];
	echo "</b></pre>After: <b><pre>".$default["after"]."</b></pre>";