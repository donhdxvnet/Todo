<script type="text/javascript" src="lib/js/Tasks.js"></script>
<?php
	require_once 'lib/php/modules/Task.class.php';
	$tasks = Task::getTasks();
	$nbTasks = count($tasks);
	//echo "<pre>"; print_r($tasks); echo "</pre>"; //debug	
?>

<ul>
<?php for ($i = 0; $i < $nbTasks; $i++){ ?>
	<li>
		<span><?= $tasks[$i]->text ?></span>
		<span>
			<a href="#" onclick="removeTask(<?= $i ?>);">
				<img src="images/delete.png" />
			</a>
		</span>
		<span>
			<a href="#" onclick="downTask(<?= $i ?>);">
				<img src="images/down.png" />
			</a>
		</span>
		<span>
			<a href="#" onclick="upTask(<?= $i ?>);">
				<img src="images/up.png" />
			</a>
		</span>
	</li>
<?php } ?>
</ul>