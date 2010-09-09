{nicEdit}
<form id="myform" method="post" action="{commentAction}">
<fieldset>
<legend>{legend}</legend>
<p><label for="author"><strong>{authorLabel}</strong></label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;({required})</font><br>
<input type="text" class="ptext" id="author" name="author" value=""></p>
{authorValidate}
<p><label for="commentEmail">{emailLabel}</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;({optional})</font><br>
<input type="text" class="ptext" name="commentEmail" id="commentEmail" value=""></p>
{emailValidate}
<p><label for="commentUrl">{url}</label><font face="Verdana, Arial, Helvetica, sans-serif" size="2">&nbsp;({optionalUrl})</font><br>
<input type="text" class="ptext" name="commentUrl" id="commentUrl" value=""></p>
{urlValidate}
<label for="comment">{contentLabel}</label><br>
<textarea name="comment" cols="{textAreaCols}" rows="{textAreaRows}" style="height: 200px; width: 400px;" id="comment"></textarea><br>
<input type="hidden" name="func" value="sendComment" >
{securityCode}
{hidden}
<p><input type="submit" class="submit" style="width:100px" value="{submit}">&nbsp;&nbsp;<input type="reset" class="submit" value="{reset}"></p>
</fieldset>
</form>