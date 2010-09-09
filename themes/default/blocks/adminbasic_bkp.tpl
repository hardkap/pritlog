<h3>{header}</h3>
<form method="post" action="{action}">
<fieldset>
<legend>{legend}</legend>
<p><label for="title">{titleLabel}</label><br>
<input type="text" class="ptitle" name="title" id="title" value="{title}"></p>
{titleValidate}
<p><label for="newpass1">{pass1}</label><br>
<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>
{pass1Validate}
<p><label for="newpass2">{pass2}</label><br>
<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>
{pass2Validate}
<p><label for="adminEmail">{emailLabel}</label><br>
<input type="text" class="ptext" name="adminEmail" id="adminEmail" value="{email}"></p>
{emailValidate}
<br><label for="posts">{aboutLabel}</label><br>
{nicEdit}
<textarea name="posts" id="posts">{about}</textarea><br><br>  
<p><input type="submit" class="submit" value="{submit}"></p>
</fieldset>
</form>