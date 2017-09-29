<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form-user">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td class="text-left">
                                        <?php echo $column_username; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $column_enabled; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $column_apikey; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php echo $column_actions; ?>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users) { ?>
                                <?php foreach ($users as $user) { ?>
                                <tr>
                                    <td class="text-left"><?php echo $user['username']; ?></td>
                                    <td class="text-left"><?php echo $user['enabled']? $column_yes : $column_no; ?></td>
                                    <td class="text-left"><?php echo $user['apikey']; ?></td>
                                    <td class="text-right">
                                        <?php if ($user['enabled']) { ?>
                                        <a target="_blank" href="<?php echo $user['connect']; ?>" data-toggle="tooltip" title="<?php echo $action_connect; ?>" class="btn btn-primary"><?php echo $action_connect; ?></a>
                                        <?php } ?>
                                        <a href="<?php echo $user['enabled']? $user['disable'] : $user['enable']; ?>" data-toggle="tooltip" title="<?php echo !$user['enabled']? $action_enable : $action_disable; ?>" class="btn btn-primary"><?php echo !$user['enabled']? $action_enable : $action_disable; ?></a>
                                        <a href="<?php echo $user['generate']; ?>" data-toggle="tooltip" title="<?php echo $action_generate; ?>" class="btn btn-primary"><?php echo $action_generate; ?></a>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php } else { ?>
                                <tr>
                                    <td class="text-center" colspan="5"><?php echo $text_no_results; ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <div class="row">
                    <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>