<div class="content-item"> 
	<h2>{header}</h2>
	{tabs}
	<form method="post" action="{action}">
	<br/>
	<table>
	<tr><th>Select</th><th>Comment Title</th><th>Posted By</th></tr>
	{comments}
	</table>
	<input type="hidden" id="notfirst" name="notfirst" value="notfirst">
	<p><input type="submit" class="submit" value="{approve}">&nbsp;&nbsp;<a href="{deletelink}"><input type="button" value="{delete}"></a></p>
	</form>
	{script}
</div>