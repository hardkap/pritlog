{loc_top}
<h3>{newEntryHeader}</h3>
<form id="myform" method="post" action="{script}/newEntrySubmit">
<fieldset>
<legend>{pageLegend}</legend>
{loc_form_top}
<p><label for="title">{title}</label><br>
<input type="text" class="ptitle" name="title" id="title" value=""></p>
{loc_content_before}
<br><label for="posts">{content}</label><br>({readmore})<br>
<textarea name="posts" cols="{textAreaCols}" rows="{textAreaRows}" style="height: 400px; width: 550px;" id="posts"></textarea><br><br>
{loc_content_after}
<p><label for="category">{category}</label><br>
<input type="text" class="ptext" id="category" name="category" ></p>
<p><label>{options}</label><br>
<input type="checkbox" name="allowComments" value="yes" checked="checked">{allowComments}<br>
<input type="checkbox" name="isPage" value="1">{isPage} <a href="javascript:alert('{isPageHelp}')">(?)</a><br>
<input type="checkbox" name="isSticky" value="yes">{isSticky}<br>
<input type="checkbox" name="isDraft" value="0">{isDraft}</p>
<input type="hidden" name="func" value="newEntrySubmit" >
{hidden}
{loc_form_bottom}
<p><input type="submit" class="submit" style="width:100px;" value="{submit}"></p>
</fieldset>
</form>
{loc_bottom}