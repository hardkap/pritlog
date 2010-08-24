{loc_top}
<h3>{header}</h3>

<form method="post" action="{script}/editEntrySubmit">
<fieldset>
<legend>{pageLegend}</legend>
{loc_form_top}
<p><label for="title">{labelTitle}</label><br>
<input type="text" class="ptitle" name="title" id="title" value="{title}"></p>
{titleValidate}
{loc_content_before}
<br><label for="posts">{labelContent}</label><br>({readmore})<br>
<textarea name="posts" cols="{textAreaCols}" rows="{textAreaRows}" style="height: 400px; width: 550px;" id="posts">{content}</textarea><br><br>
{loc_content_after}
<p><label for="category">{labelCategory}</label><br>
<input type="text" class="ptext" id="category" name="category" value="{category}"></p>
{categoryValidate}
<p><label>{options}</label><br>
<input type="checkbox" name="allowComments" value="yes" {checkAllowComments}>{allowComments}<br>
<input type="checkbox" name="isPage" value="1" {checkIsPage}>{isPage} <a href="javascript:alert('{isPageHelp}')">(?)</a><br>
<input type="checkbox" name="isSticky" value="yes" {checkSticky}>{isSticky}<br>
<input type="checkbox" name="isDraft" value="0" {checkDraft}>{isDraft}</p>
{hidden}
{loc_form_bottom}
<p><input type="submit" class="submit" style="width:100px;" value="{submit}"></p>
</fieldset>
</form>
{loc_bottom}