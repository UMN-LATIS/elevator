		<div class="footerBlock">
			<script>
			$(document).ready(function() {
				$(".rightPane .footerBlock").height($(window).height() - 200);
			});
			$(window).resize(function() {
				$(".rightPane .footerBlock").height($(window).height() - 200);
			});
			</script>
		</div>
		</div> <!-- end tab-content -->

	</div> <!-- end col-sm-9 -->
</div> <!-- end row -->


</form>

<?$this->load->view("handlebarsTemplates");