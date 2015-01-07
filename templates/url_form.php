
<style type="text/css">

html body {
	margin-top: 50px !important;
}

#top_form {
	position: fixed;
	top:0;
	left:0;
	width: 100%;
	
	margin:0;
	
	z-index: 2100000000;
	-moz-user-select: none; 
	-khtml-user-select: none; 
	-webkit-user-select: none; 
	-o-user-select: none; 
	
	border-bottom:1px solid #151515;
	
    background:#FFC8C8;
	
	height:45px;
	line-height:45px;
}

</style>

<script src="//www.php-proxy.com/assets/url_form.js"></script>

<div id="top_form">

	<div style="width:800px; margin:0 auto;">
	
		<form method="post" action="<?=$script_base;?>" target="_top" style="margin:0; padding:0;">
			<input type="button" value="Home" onclick="window.location.href='<?=$script_base;?>'">
			<input type="text" style="width:550px;" name="url" value="<?=$url;?>" autocomplete="off">
			<input type="hidden" name="form" value="1">
			<input type="submit" value="Go">
		</form>
		
	</div>
	
</div>

<script type="text/javascript">
	smart_select(document.getElementsByName("url")[0]);
</script>


<? echo $content; ?>
