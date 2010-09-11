<div class="content-item">
<h2>{header}</h2>
<form id="myform" method="post" action="{action}">
<p><label for="addAuthor">{authorLabel}</label><br>
<input type="text" class="ptext" name="addAuthor" id="addAuthor" value=""></p>
<p><label for="newpass1">{newPass1}</label><br>
<input type="password" class="ptext" name="newpass1" id="newpass1" value=""></p>
<p><label for="newpass2">{newPass2}</label><br>
<input type="password" class="ptext" name="newpass2" id="newpass2" value=""></p>
<p><label for="authorEmail">{authorEmail}</label><br>
<input type="text" class="ptext" name="authorEmail" id="authorEmail" value=""></p>
<input type="hidden" name="func" value="registerPageSubmit" >
{securityCode}
{loc_form}
<p><input type="submit" class="submit" value="{submit}"></p>
</form>
</div>