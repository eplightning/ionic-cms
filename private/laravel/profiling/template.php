<!-- ANBU - LARAVEL PROFILER -->
<style type="text/css">
<?php

echo

file_get_contents
(path('sys').'profiling/profiler.css')

?>
<?php

echo

file_get_contents(path('sys').'profiling/profiler.css')

?>
</style>
<div class="anbu">
	<div class="anbu-window">
		<div class="anbu-content-area">
			<div class="anbu-tab-pane anbu-table anbu-log">
				<?php if (count($logs) > 0): ?>
					<table>
						<tr>
							<th>Type</th>
							<th>Message</th>
						</tr>
						<?php foreach ($logs as $log): ?>
							<tr>
								<td class="anbu-table-first">
									<?php echo $log[0] ?>
								</td>
								<td>
									<?php echo $log[1] ?>
								</td>
						<?php endforeach; ?>
						</tr>
					</table>
				<?php else: ?>
					<span class="anbu-empty">There are no log entries.</span>
				<?php endif;?>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-sql">
				<?php if (count($queries) > 0): ?>
					<table>
						<tr>
							<th>Time</th>
							<th>Query</th>
						</tr>
						<?php foreach ($queries as $query): ?>
							<tr>
								<td class="anbu-table-first">
									<?php echo  $query[1] ?>ms
								</td>
								<td>
									<pre><?php echo  $query[0] ?></pre>
								</td>
							</tr>
						<?php endforeach;?>
					</table>
				<?php else: ?>
					<span class="anbu-empty">There have been no SQL queries executed.</span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<ul id="anbu-open-tabs" class="anbu-tabs">
		<li><a data-anbu-tab="anbu-log" class="anbu-tab" href="#">Log <span class="anbu-count"><?php echo  count($logs) ?></span></a></li>
		<li>
			<a data-anbu-tab="anbu-sql" class="anbu-tab" href="#">SQL
				<span class="anbu-count"><?php echo  count($queries) ?></span>
				<?php if (count($queries)): ?>
				<span class="anbu-count"><?php echo  array_sum(array_map(function($q) { return $q[1]; }, $queries)) ?>ms</span>
				<?php endif; ?>
			</a>
		</li>
		<li><a class="anbu-tab" href="#">Time <span class="anbu-count"><?php echo round($time, 3) ?>s</span></a></li>
		<li><a class="anbu-tab" href="#">Memory <span class="anbu-count"><?php echo $memory ?></span></a></li>
		<li><a class="anbu-tab" href="#">Peak <span class="anbu-count"><?php echo $memory_peak ?></span></a></li>
		<li class="anbu-tab-right"><a id="anbu-hide" href="#">&#8614;</a></li>
		<li class="anbu-tab-right"><a id="anbu-close" href="#">&times;</a></li>
		<li class="anbu-tab-right"><a id="anbu-zoom" href="#">&#8645;</a></li>
	</ul>

	<ul id="anbu-closed-tabs" class="anbu-tabs">
		<li><a id="anbu-show" href="#">&#8612;</a></li>
	</ul>
</div>

<script>window.jQuery || document.write("<script src='//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'>\x3C/script>")</script>
<script><?php echo file_get_contents(path('sys').'profiling/profiler.js') ?></script>
<!-- /ANBU - LARAVEL PROFILER -->