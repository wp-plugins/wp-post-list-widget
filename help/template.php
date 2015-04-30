Use custom template for the loop or leave unchecked and use the default, which is:
<?php
	$default=$this->getDefaultTemplate();
	echo "<br/>Before: <b><xmp>".$default["before"];
	echo "</xmp><br/></b>Template: <b><xmp>".$default["template"];
	echo "</xmp><br/></b>After: <b><xmp>".$default["after"]."</xmp></b>";