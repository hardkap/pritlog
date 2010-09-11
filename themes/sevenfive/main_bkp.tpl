	<div id="menu"> 
	<ul>
		{loc_menu_top}
		{menu}
		{loc_menu_bottom}
	</ul>	
	</div>
</div> <!-- header -->

<div id="content"> 
	{loc_main_top}
	{content}
	<div class="pagination content-item"> 
	{pagenav}	
	</div> <!-- page-nav --> 

	{loc_main_bottom}	
	
</div> <!-- content --> 

<!-- 
<div class="span-24">
{tagCloud}
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

<div  class="span-6  last">
<h3>{popularHeader}</h3>
{popular}
</div>

<div  class="span-6 last">
<h3>{commentsHeader}</h3>
{comments}
</div>


<div  class="span-6">
<h3>{aboutHeader}</h3>
{about}
</div>

--> 

{loc_main_after}

<script type="text/javascript" src="{blogPath}/themes/sevenfive/javascripts/seven.js"></script>

<div id="footer"> 
	<h3>{footer}</h3>
	<!-- PLEASE DONT REMOVE THIS FOOTER/COPYRIGHT WITHOUT PERMISSION FROM THE AUTHOR -->
	<p>Site Design by Jason Schuller &amp; <a href="http://www.press75.com/" title="Press75.com" >Press75.com</a></p> 
</div> <!-- footer --> 

</body>
</html>
