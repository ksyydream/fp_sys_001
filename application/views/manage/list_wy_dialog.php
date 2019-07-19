<div id="detail">
<form id="pagerForm" method="post" action="<?php echo site_url('manage/list_wy_dialog')?>">
	<input type="hidden" name="pageNum" value="<?php echo $pageNum;?>" />
	<input type="hidden" name="numPerPage" value="<?php echo $numPerPage;?>" />
	<input type="hidden" name="orderField" value="<?php echo $this->input->post('orderField');?>" />
	<input type="hidden" name="orderDirection" value="<?php echo $this->input->post('orderDirection');?>" />
</form>

<div class="pageHeader" id="dialog">
	<form onsubmit="return dialogSearch(this);" action="<?php echo site_url('manage/list_wy_dialog')?>" method="post">
		<div class="searchBar">

		</div>
	</form>
</div>

<div class="pageContent">

	<div layoutH="116" id="list_warehouse_in_print">
	<table class="list" width="100%" targetType="dialog" asc="asc" desc="desc">
		<thead>
			<tr>
				<th>物业类型</th>
				<th width="30">选择</th>
			</tr>
		</thead>
		<tbody>
            <?php
                if (!empty($res_list)):
            	    foreach ($res_list as $row):
            ?>
            			<tr target="id" rel=<?php echo $row->id; ?>>
            				<td><?php echo $row->wy;?></td>
							<td>
								<a class="btnSelect" onclick="bringBack_current('<?php echo $row->id;?>','<?php echo $row->wy;?>');" href="javascript:;" >选择</a>
							</td>
            			</tr>
            <?php
            		endforeach;
            	endif;
            ?>
		</tbody>
	</table>
	</div>
	<div class="panelBar" >
		<div class="pages">
			<span>显示</span>
			<select name="numPerPage" class="combox" onchange="dialogPageBreak({numPerPage:this.value})">
				<option value="20" <?php echo $this->input->post('numPerPage')==20?'selected':''?>>20</option>
				<option value="50" <?php echo  $this->input->post('numPerPage')==50?'selected':''?>>50</option>
				<option value="100" <?php echo $this->input->post('numPerPage')==100?'selected':''?>>100</option>
				<option value="200" <?php echo $this->input->post('numPerPage')==200?'selected':''?>>200</option>
			</select>
			<span>条，共<?php  echo $countPage;?>条</span>
		</div>
		<div class="pagination" targetType="dialog" totalCount="<?php echo $countPage;?>" numPerPage="<?php echo $numPerPage;?>" pageNumShown="10" currentPage="<?php echo $pageNum;?>"></div>
	</div>
</div>
</div>
<script>
	function bringBack_current(id,wy){
		$.bringBack({id:id,wy:wy})
	}
</script>