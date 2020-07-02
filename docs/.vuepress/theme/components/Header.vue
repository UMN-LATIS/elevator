<template>
  
			<header class="umnhf" id="umnhf-h" role="banner">
                 
            <link rel="stylesheet" href="https://chimein2.cla.umn.edu/umn/css/2015-tc.css">
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css?family=Amatic+SC" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css?family=Itim" rel="stylesheet">
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

				<!-- Skip Links: Give your nav and content elements the appropriate ID attributes -->
				<div id="skipLinks"><a href="#main-nav">Main navigation</a><a href="#main-content">Main content</a></div>
				<div class="printer"><div class="left"></div><div class="right"><strong>University of Minnesota</strong><br />http://twin-cities.umn.edu/<br />612-625-5000</div></div>
				<div class="umnhf" id="umnhf-h-mast">
					<a class="umnhf" id="umnhf-h-logo" href="http://twin-cities.umn.edu/"><span>Go to the U of M home page</span></a>
					<ul class="umnhf" id="umnhf-h-ql">
						<li><a href="http://onestop.umn.edu/">One Stop</a></li>
						<li class="umnhf"><a href="https://www.myu.umn.edu/">MyU <span></span>: For Students, Faculty, and Staff</a></li>
					</ul>
				<!-- Button below is for dropdown toggle, only visible on mobile screens. If using
				a non-dropdown version you can delete this tag -->
				<button class="umnhf" id="umnhf-m-search">Search</button>
			</div>
			<form class="umnhf umnhf-search" id="umnhf-h-search" action="//search.umn.edu/tc/" method="get" title="Search Websites and People" role="search">
				<label class="umnhf" for="umnhf-h-st">Search</label>
				<input class="umnhf" id="umnhf-h-st" type="text" name="q" />
				<label class="umnhf" for="umnhf-h-sb">Submit search query</label>
				<input class="umnhf" id="umnhf-h-sb" type="submit" value="">
			</form>
		</header>

</template>

<style lang="stylus">

@media (max-width: $MQMobile) 
    .umnhf-search
        display:none !important;

</style>

<script>

export default {
    mounted() {
        let umnhf = document.createElement('script');    
        umnhf.setAttribute('src',"https://chimein2.cla.umn.edu/umn/js/umnhf-2015.js");
        document.head.appendChild(umnhf);
    }
}
</script>

