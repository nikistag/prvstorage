<div class="row" style="margin-bottom:0px;">
    <div><span>
            <?= $quota ?>
        </span> % of disk space in use.
        <?= $disk_free_space ?> Gb free space
    </div>
    <div class="progress">
        <div class="determinate" style="width:<?= $quota ?>%;"></div>
    </div>
</div>