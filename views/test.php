<?php
	use yii\web\View;

	$this->registerJs("
		console.log('HOLA');
	",View::POS_END,'hola2');
	echo "TEST VIEW.".__FILE__;
?>
