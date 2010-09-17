<div class="span-24">
{tagCloud}
</div>

<div id="all" class="span-24">

<!-- Main content - that has the posts begins here -->

<div id="content" class="span-16">

<div id="noticehd"></div>
{loc_main_top}
{content}
{loc_main_bottom}
<center>{pagenav}</center>
</div>

<!-- Right sidebar -->

<div class="span-6">

<div id="menu" class="span-6">

<div class="span-6  last">
<br>
{loc_sidebar_top}
</div>

<div class="span-6  last">
<br>
{shareme}
</div>


<div class="span-6  last">
    <br/>
    <form name="form1" method="post" id="searchform" action={script}/searchPosts>
    <input type="text" class="s" name="searchkey">
    <input type="hidden" name="do" value="search">
    <input type="submit" class="submit" name="Submit" value="Search"><br />
    </form>
</div>

<div  class="span-6 last">
<h3>{latestEntriesHeader}</h3>
{latestEntries}
</div>

<div  class="span-6  last">
<h3>{menuHeader}</h3>
{loc_menu_top}
{menu}
{loc_menu_bottom}
</div>

<div  class="span-6  last">
<h3>{categoriesHeader}</h3>
{categories}
</div>

<div class="span-6  last">
<h3>{pagesHeader}</h3>
{pages}
</div>

<div  class="span-6  last">
<h3>{linksHeader}</h3>
{links}
</div>

<div  class="span-6  last">
<h3>{statsHeader}</h3>
{stats}
</div>

<div class="span-6  last sidelast">
<br>
{loc_sidebar_bottom}
</div>

</div>

</div>



<div id="foot" class="span-24">

<div  class="span-7  prepend-1 append-1">
<h3>{popularHeader}</h3>
{popular}
</div>

<div  class="span-7 append-1">
<h3>{commentsHeader}</h3>
{comments}
</div>


<div  class="span-6">
<h3>{aboutHeader}</h3>
{about}
</div>

</div>

</div>

{loc_main_after}

<div id="footer">
<!-- PLEASE DONT REMOVE THIS FOOTER/COPYRIGHT WITHOUT PERMISSION FROM THE AUTHOR -->
{footer}
</div>

</div>

<script type="text/javascript" src="{blogPath}/javascripts/jquery.min.js"></script>
<script type="text/javascript" src="{blogPath}/javascripts/jquery.jgrowl.min.js"></script>
<script src="{blogPath}/javascripts/main.js" type="text/javascript"></script>

<script>{growlmsg}</script>

</body>
</html>
