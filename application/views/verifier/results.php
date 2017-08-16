<?php
$percent = (float)($correct / $total) * 100;
$this->load->helper('url');
?>
<div class="text-center">
    <h1>Accuracy: <?php echo $percent . "%"?></h1>
    <a class="btn btn-primary" href="<?php echo base_url('verifier/verify') ?>" title="Start Again">Start Again</a>
</div>