<?php
$progress = (float)($count / $total) * 100;
$this->load->helper('url');
?>
<div class="panel panel-info" style="width:50%; margin: 0 auto;">
    <div class="panel-heading text-center">
        <h3 class="panel-title"><?php echo $image->filename . '.' . $image->type ?></h3>
    </div>
    <div class="panel-body" style="width:70%; margin: 0 auto">
        <div class="thumbnail">
            <img src="<?php echo $image->src ?>" alt="<?php echo $image->alt ?>" style="border-radius:5px;">
            <div class="caption">
                <h3><b>Location</b> <?php echo $image->location_name ? $image->location_name : "N/A" ?></h3>
                <p>Latitude: <?php echo $image->latitude ?> | Longitude: <?php echo $image->longitude ?></p>

                <div>
                    <b>Associated Text:</b>
                    <p><?php echo $image->related_texts ?></p>
                    <p><?php echo $image->alt ?></p>
                </div>
            </div>
        </div>
        <div>
            <p>
                <small>Is the geo location info of this image correct?</small>
            </p>
            <p>
                <a href="<?php echo base_url('verifier/verify/1') ?>" class="btn btn-success" role="button">Yes</a>
                <a href="<?php echo base_url('verifier/verify/0') ?>" class="btn btn-danger" role="button">No</a>
            </p>
        </div>
        <div>
            <b>Number of correct images:</b>
            <span><?php echo $correct ?></span>
        </div>
        <div class="progress">
            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $progress ?>"
                 aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $progress ?>%">
                <span class="sr-only"><?php echo $progress ?>% Complete</span>
            </div>
        </div>
    </div>
</div>