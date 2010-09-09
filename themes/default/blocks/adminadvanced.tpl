<h3>{header}</h3>
{tabs}
<div class="{msgclass}">{msgtext}</div>
<form method="post" action="{action}">
<fieldset>
<legend>{legend}</legend>
{theme}
{language}
{privacy}
<p><label for="metaDesc">{metaDescLabel}</label><br>
<input type="text" class="ptext" name="metaDesc" id="metaDesc" value="{metaDesc}"></p>
<p><label for="metaKeywords">{metaKeywordsLabel}</label><br>
<input type="text" class="ptext" name="metaKeywords" id="metaKeywords" value="{metaKeywords}"></p>
<p><label for="commentsMaxLength">{commentsMaxLengthLabel}</label><br>
<input type="text" class="ptext" name="commentsMaxLength" id="commentsMaxLength" value="{commentsMaxLength}"></p>
<p><label for="commentsForbiddenAuthors">{commentsForbiddenAuthorsLabel}</label><br>
<input type="text" class="ptext" name="commentsForbiddenAuthors" id="commentsForbiddenAuthors" value="{commentsForbiddenAuthors}"></p>
<p><label for="statsDontLog">{statsDontLogLabel}</label><br>
<input type="text" class="ptext" name="statsDontLog" id="statsDontLog" value="{statsDontLog}"></p>
<p><label for="entriesOnRSS">{entriesOnRSSLabel}</label><br>
<input type="text" class="ptext" name="entriesOnRSS" id="entriesOnRSS" value="{entriesOnRSS}"></p>
<p><label for="entriesPerPage">{entriesPerPageLabel}</label><br>
<input type="text" class="ptext" name="entriesPerPage" id="entriesPerPage" value="{entriesPerPage}"></p>
<p><label for="menuEntriesLimit">{menuEntriesLimitLabel}</label><br>
<input type="text" class="ptext" name="menuEntriesLimit" id="menuEntriesLimit" value="{menuEntriesLimit}"></p>
<p><label for="timeoutDuration">{timeoutDurationLabel}</label><br>
<input type="text" class="ptext" name="timeoutDuration" id="timeoutDuration" value="{timeoutDuration}"></p>
<p><label for="limitLogins">{limitLoginsLabel}</label><br>
<input type="text" class="ptext" name="limitLogins" id="limitLogins" value="{limitLogins}"></p>
<p><label for="menuLinks">{menuLinksLabel}</label><br>
<textarea name="menuLinks" id="menuLinks" rows="5" cols="30" value="">{menuLinks}</textarea></p>
<p><label for="ipBan">{ipBanLabel}</label><br>
<textarea name="ipBan" id="ipBan" rows="5" cols="25" value="">{ipBan}</textarea></p>

{sendMailComments}
{commentsSecurityCode}
{authorEditPost}
{authorDeleteComment}
{showCategoryCloud}
{allowRegistration}
{sendRegistMail}
{cleanUrl}
{commentModerate}

{hidden}
<input type="hidden" id="submitted" name="submitted" value="yes">
<br><br><p><input type="submit" class="submit" value="{submit}"></p>
</fieldset>
</form>