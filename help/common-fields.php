You can customize excerpts by adding parameters:<br/>
<strong>{{excerpt|LENGTH|MORE|TAGS}}</strong><br/>
LENGTH - the length in words ( default: <?php echo self::MY_EXCERPT_DEAFULT_LENGTH; ?> )<br/>
MORE - more sign ( default: <?php echo self::MY_EXCERPT_DEAFULT_MORE; ?> )<br/>
TAGS - allowed tags ( default: <xmp><?php echo self::MY_EXCERPT_DEAFULT_TAGS; ?></xmp> )

<br/><br/>

If you print a value of a multiple custom variable, you can use parameters with subtemplate like this:<br/>
<strong>{{var_name|NUMBER}}</strong> returns a single value<br/>
<strong>{{var_name|STRING}}</strong> returns an array joined by the string<br/>
<strong>{{var_name|STRING_1|STRING_2}}</strong> returns an array embraced with the strings<br/>
<strong>{{var_name|NUMBER_1|NUMBER_2|STRING_1}}</strong> returns a slice of the array joined by the string<br/>
<strong>{{var_name|NUMBER_1|NUMBER_2|STRING_1|STRING_2}}</strong> returns a slice of the array embraced with the strings<br/>
NUMBER_2 is optional in the last 2 examples