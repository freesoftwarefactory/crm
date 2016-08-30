<?php 
	use yii\helpers\Url;
	use app\assets\AppAsset;
	
	AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
	<head>
		<?php 
		/*
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
			<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		*/ 
		?>
    	<?php $this->head() ?>
	</head>
	<body>
		<?php $this->beginBody() ?>
		<div class='row'>
			<div class='col-md-12'>
				<div class='panel panel-default'>
					<div class='panel-body'>
						<a href='<?=Url::toRoute('/crm/find');?>'>Buscar</a>
						<a href='<?=Url::toRoute('/crm/create');?>'>Crear</a>
					</div>
				</div>
				<div class='panel panel-default'>
					<div class='panel-body'>
						<div class='row'>
							<div class='col-md-6'><?=$content;?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php $this->endBody(); ?>
	</body>
</html>
<?php $this->endPage(); ?>
