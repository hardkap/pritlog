<h3>{header}</h3>
<form method="post" action="{action}">
<fieldset>
<legend>{legend}</legend>
<p><label for="addAuthor">{authorLabel}</label><br>
<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>
{authorValidate}
<p><label for="newpass1">{newPass1}</label><br>
<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>
{newPass1Validate}
<p><label for="newpass2">{newPass2}</label><br>
<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>
{newPass2Validate}
<p><label for="authorEmail">{authorEmail}</label><br>
<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>
{authorEmailValidate}
{securityCode}
{loc_form}
<p><input type="submit" class="submit" value="{submit}"></p>
</fieldset>
</form>