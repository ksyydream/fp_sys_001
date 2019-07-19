<style type="text/css">
    .file-box{ position:relative;width:340px}
    .btn{ background-color:#FFF; border:1px solid #CDCDCD;height:21px; width:70px;}
    .file{ position:absolute; top:0; right:80px; height:24px; filter:alpha(opacity:0);opacity: 0;width:300px }
</style>
<div class="pageContent">
    <form method="post" enctype="multipart/form-data" action="<?php echo site_url('manage/save_product_first');?>" class="pageForm required-validate" onsubmit="return validateCallback(this, dialogAjaxDone);">
        <div class="pageFormContent" layoutH="55">
            <fieldset>
                <legend>一级类别</legend>
                <dl>
                    <dt>类别名称：</dt>
                    <dd>
                        <input type="hidden" name="id" value="<?php if(!empty($id)) echo $id;?>">
                        <input name="first_name" type="text" class="required" value="<?php if(!empty($first_name)) echo $first_name;?>" />
                    </dd>
                </dl>
				<dl>
					<dt>状态：</dt>
					<dd>
                        <select class="combox" name="flag">
                            <option value="1" <?php if(!empty($flag)){if($flag==1){echo 'selected';}}else{echo 'selected';}?>>使用中</option>
                            <option value="-1" <?php if(!empty($flag)){if($flag==-1){echo 'selected';}}?>>已作废</option>
                        </select>
					</dd>
				</dl>
            </fieldset>
        </div>
        <div class="formBar">
            <ul>
                <li><div class="buttonActive"><div class="buttonContent"><button type="submit" class="icon-save">保存</button></div></div></li>
                <li><div class="button"><div class="buttonContent"><button type="button" class="close icon-close">取消</button></div></div></li>
            </ul>
        </div>
    </form>
</div>
<script>

</script>
