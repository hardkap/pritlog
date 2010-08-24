<h3>{header}</h3>
<form name="form1" method="post" action="{action}">
<table>
<tr><td>{user}</td>
<td><input class="s" name="author" type="text" id="author">&nbsp;&nbsp;('admin' for master user)</td></tr>
{userValidate}
<tr><td>{password}</td>
<td><input class="s" name="pass" type="password" id="pass"></td></tr>
{passValidate}
{loc_form}
</tr><tr><td>&nbsp;</td><td><input type="submit" class="submit" name="Submit" value="{submit}"></td>
</tr></table></form>
[{forgotPassLink}]
{register}
{loc_bottom}
