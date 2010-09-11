<div class="content-item"> 
	<h2>{header}</h2>
	{tabs}
	<br/>
	<form method="post" class="adminPage" action="{action}">
	<p><label for="addAuthor">{author}</label><br>
	<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>
	<p><label for="newpass1">{pass1}</label><br>
	<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>
	<p><label for="newpass2">{pass2}</label><br>
	<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>
	<p><label for="authorEmail">{email}</label><br>
	<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>
	<input type="hidden" id="authoradd" name="authoradd" value="yes">
	<p><input type="submit" class="submit" value="{submit}"></p>
	</form>
</div>
