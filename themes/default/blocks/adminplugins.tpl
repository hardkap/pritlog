<div class="content-item"> 
	<h2>{header}</h2>
	{tabs}
	<form method="post" action="{action}">
	<br/>
	<table>
	<tr><th>Active</th><th>Plugin</th><th>Author</th><th>Description</th></tr>
	{plugins}
	</table>
	<input type="hidden" id="notfirst" name="notfirst" value="notfirst">
	<p><input type="submit" class="submit" value="{submit}"></p>
	</form>
	{script}
</div>