This field must contain a PHP code, that will be executed before the query.
If a valid array of arguments provided, i.e.
<b><pre>
$args=array(
	'date_query' => array(
		array(
			'year'  => 2015
		),
	),
);
</pre></b>
It will be used in <b><pre>new WP_Query( $args );</pre></b> command.<br/><br/>
It will be a visible variable to use in template fields of this form.<br/> You can use the one above as <b>{{args['date_query'][0]['year']}}</b><br/><br/>
If <b>$args</b> isn't set, a default value is used, which is:
<b><pre><?php print_r($this->getDefaultArgs()); ?></pre></b>

for more information see the <a href="https://codex.wordpress.org/Class_Reference/WP_Query" target="_new">reference</a>.