	<ul>
	{menu}
	</ul>
	<div class="header-strip"> </div> 
</div>

<div id="main"> 
<div id="content"> 
	{loc_main_top}
	{content}
	{loc_main_bottom}
	<center>{pagenav}</center>
</div> <!-- end content -->

<div style="clear:both;"></div> 

<div class="footer-sidebar"> 
	<div id="sidebar1" class="sidecol"> 
		<ul>
			<li>
				<h2>{latestEntriesHeader}</h2>
				<ul>
				{latestEntries}
				</ul>
			</li>
			<li>
				<h2>{commentsHeader}</h2>
				<ul>
				{comments}
				</ul>
			</li>	
			<li>
				<h2>Search</h2>
				<form name="form1" method="post" id="searchform" action={script}/searchPosts>
				<input type="text" class="s" name="searchkey">
				<input type="hidden" name="do" value="search">
				<input type="submit" class="submit" name="Submit" value="Search"><br />
				</form>
			</li>
		</ul> 
	</div> 	<!-- end sidebar1 -->
	<div id="sidebar2" class="sidecol"> 
		<ul> 
			<li> 
				<h2>{categoriesHeader}</h2>
				<ul>
				{categories}
				</ul>
			</li>	
			<li> 
				<h2>{pagesHeader}</h2>
				<ul>
				{pages}
				</ul>
			</li>
		</ul> 
	</div> 	
	<div style="clear:both;"></div> 
</div> 				

</div>

<div id="footer"> 
	<!-- <p><span><a href="http://clear.kera.la/wp-admin/" title="Site Admin">Site Admin</a> | Theme by <a href="http://www.diovo.com/links/clear/" title="Diovo">Niyaz</a></span><strong>Clear</strong> Copyright &copy; 2010 All Rights Reserved</p> -->
	{footer}
</div> <!-- end footer -->


{loc_main_after}

</body>
</html>
