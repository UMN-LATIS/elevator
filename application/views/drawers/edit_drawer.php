<li class="divider"></li>
<?if($this->user_model->getAccessLevel("drawer", $this->doctrine->em->getReference("Entity\Drawer", $drawerId)) >= PERM_CREATEDRAWERS):?>
<li><a href="<?=instance_url("permissions/edit/drawer/".$drawerId)?>" >Drawer Permissions</a></li>
<li><a href="<?=instance_url("drawers/delete/".$drawerId)?>" onClick="return confirm('Are you sure you wish to delete this drawer?')">Delete Drawer</a></li>
<?endif?>
<?if($this->user_model->getAccessLevel("drawer", $this->doctrine->em->getReference("Entity\Drawer", $drawerId)) >= PERM_ORIGINALSWITHOUTDERIVATIVES):?>
<li><a href="<?=instance_url("drawers/downloadDrawer/" . $drawerId)?>">Download Drawer</a></li>
<?endif?>
