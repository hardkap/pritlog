<form method="post" action="{action}">
<fieldset>
<legend>{legend}</legend>
<table>
<tr><td><strong>Author: </strong>{author}<br><br></td>
<td><label for="authorEmail">{emailLabel}</label><br>
<input type="text" name="authorEmail" id="authorEmail" value="{email}"></td></tr>
<tr><td><label for="{pass1}">{pass1Label}</label><br>
<input type="password" name="{pass1}" id="{pass1}" value=""></td>
<td><label for="{pass2}">{pass2Label}</label><br>
<input type="password" name="{pass2}" id="{pass2}" value=""></td></tr>
</table>
<input type="hidden" id="authoredit" name="authoredit" value="yes">
<input type="submit" value="{submit}">&nbsp;&nbsp;
<input type="checkbox" name="deleteAuthor" value="1">{delete}
<input name="author" type="hidden" id="author" value="{author}">
<input name="authornum" type="hidden" id="authornum" value="{authornum}">
</fieldset>
</form>