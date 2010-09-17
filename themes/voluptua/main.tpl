<div id="entries"> 
	{loc_main_top}
	{content}
	{loc_main_bottom}
	<center>{pagenav}</center>
</div> <!-- end entries -->

<div id="sidebar"> 
	<div id="sidebarright"> 
		<h3>Search</h3> 
		<form name="form1" method="post" id="searchform" action={script}/searchPosts>
		<input type="text" class="s" name="searchkey">
		<input type="hidden" name="do" value="search">
		<input type="submit" class="submit" name="Submit" value="Search"><br />
		</form>
		<br/>
		<h3>{menuHeader}</h3>
		<ul>
		{loc_menu_top}
		{menu}
		{loc_menu_bottom}
		</ul>
		<h3>{commentsHeader}</h3>
		<ul>{comments}</ul>
		<h3>{linksHeader}</h3>
		<ul>
		{links}
		</ul>
		<h3>{pagesHeader}</h3>
		<ul>{pages}</ul>
		{loc_sidebar_top}
	
	</div><!--sidebarright--> 		
	
	<div id="sidebarleft"> 
		<h3>{aboutHeader}</h3>
		<ul>
		{about}
		</ul>
		<h3>{popularHeader}</h3>
		<ul>{popular}</ul>
		<h3>{latestEntriesHeader}</h3>
		<ul>{latestEntries}</ul>
		<h3>{statsHeader}</h3>
		{stats}

		{loc_sidebar_bottom}

	</div><!--end sidebarleft-->				


</div><!--end sidebar--> 


{loc_main_after}

<div id="footer">

{tagCloud}	

<!-- PLEASE DONT REMOVE THIS FOOTER/COPYRIGHT WITHOUT PERMISSION FROM THE AUTHOR -->
<div class="myfooter">
{footer}
<br/>
<a href="http://www.upstartblogger.com/upstart-blogger-wordpress-theme-voluptua">Voluptua</a> Wordpress theme modified for Pritlog by <a href="http://hardkap.com">Hardkap</a>
</div>
</div>

<script type="text/javascript" src="{blogPath}/javascripts/jquery.min.js"></script>
<script type="text/javascript" src="{blogPath}/javascripts/jquery.jgrowl.min.js"></script>
<script src="{blogPath}/javascripts/main.js" type="text/javascript"></script>

<script>{growlmsg}</script>

</body>
</html>
