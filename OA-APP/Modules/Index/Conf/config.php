<?php
	return array(
		/***************RBAC***********************************/
	'RBAC_SUPERADMIN'=>'admin',//超级管理员名称
	'ADMIN_AUTH_KEY'=>'superadmin',//超级管理员识别号
	'USER_AUTH_ON'=>true, //是否开启验证
	'USER_AUTH_TYPE'=>1, //1是登陆验证 2是实时验证
	'USER_AUTH_KEY'=>'id',//用户识别号
	'REQUIRE_AUTH_MODULE'=>'',
	'REQUIRE_AUTH_ACTION'=>'',
	'NOT_AUTH_MODULE'=>'Index,Mail,Individual,Search,Profile',//无需验证的模块（控制器）
	'NOT_AUTH_ACTION'=>'contact,newContact,ajaxNewContact,ajaxDisplay,closeWindow,
						,regulation_index,addRegulationHandle,modifyRegulationHandle,
						,show_notify,show_message,handle,listenMess,detailNotify,detailMessage,moreNotify,moreMessage,memo,memoHandle,myMemo,toggleMemo,deleteMemo,detailMemo,ajaxMemo,notifyManage_detail,
						,meet_index,search,keysearch,outMeetHandle,outkeysearch,deleteMeet,meet_gather,outmeet_detail,modify_outmeet,delete_outmeet,excel,outmeet_look,leader_modify_handle,
						,show_file,show_outfile,outFileHandle,more,delete,file_detail,
						,excel,ajaxWeek,createDutyTable,createHanle,dutyUrgentHandle,dutyUrgentSearch,dutyUrgentDownload,addDutyer,addDutyerHandle,modifyDutyer,deleteDutyer,ajaxDutyer,ajaxUrgentDutyer,dutyTableManage,deleteDutyTable,dutyUrgentFillHandle,dutyPublish,excelDutyUrgent,
						,information_index,adopt_word,notify,publish_handle,detail,report,report_handle,agree,disagree,modifyInfoReport,modifyInfoReportHandle,confirmReported,confirmAdopt,individual,word,appro_handle,myappro,approdetail,appro_receive,appro_collect,appro_draft,appro_finish,deleteApproFile,propa,propa_handle,propadetail,subPropa,mypropa,deleteMyPropa,addreport,addAppro,
						,secondHandle,thirdHandle,save,deletePetition,reportClass,addReportClass,addReportClassHandle,modifyReportClass,modifyReportClassHandle,deleteReportClass,ajaxReportClass,ajaxTown,add_town,addTown,addTownHandle,deleteTown,modifyTown,modifyTownHandle,deleteTown,deletePetition,detailPetition,modifyPetition,rollback,
						,show_record,show_judge,quarter,record,recordHandle,judge,judgeHandle,quarterLook,detail,download_quarter,quarterJudgeGather,quarterJudgeGatherHandle,judgeGatherLook,checkAttendance,addCheckAttendanceHandle,checkAttDetail,addPersonnelInfo,addPersonnelInfoHandle,personnelInfoDetail,checkAskLeave,askLeaveDetail,personnelInfo,addPersonnelInfo,addPersonnelInfoHandle,personnelInfoDetail,quarterDelete,judgeGatherManage,deleteQuarterJudgeItem,checkAttManage,deleteCheckAttItem,askLeaveManage,deletePersonnelInfoItem,modifyPersonnelInfoItem,show_record,modify_record,show_judge,judge_handle,agree_judge,modify_judge,judge_excel,setModifyDeadline,setDeadlineHandle,gatherCheckAttendanceHandle,modifyGatherCheck,addCheckAttendance,addCheckAttendanceHandle,generateCheckAttendance,addAskLeave,addAskLeaveHandle,generateAskLeave,deleteGatherCheck,detailGatherCheck,gatherExcel,detailGatherAsk,modifyGatherAsk,deleteGatherAsk,gatherAskLeaveHandle,detailAllAsk,deleteAllAsk,modifyAllAsk,gatherAskExcel,
						,stock,stockHandle,stockClass,addStockClass,addStockClassHandle,modifyStockClass,modifyStockClassHandle,deleteStockClass,ajaxStockClass,modifyStock,modifyStockHandle,deleteStock,itemApply_index,itemApply,itemApplyHandle,itemClass,addItemClass,addItemClassHandle,ajaxItemClass,maintain,ajaxMaintainClass,maintainHandle,deleteMaintain,modifyMaintain,modifyMaintainHandle,vehicleMaintain,vehicleMaintainHandle,vehicleOil,vehicleOilHandle,vehicleETC,vehicleETCHandle,plateManage,addPlateHandle,modifyPlate,modifyPlateHandle,deletePlate,ajaxPlate,modifyVehicleMaintain,modifyVehicleMaintainHandle,modifyOil,modifyOilHandle,modifyETC,modifyETCHandle,deleteVehicleMaintain,deleteOil,deleteETC,
						,contact_index,ajaxSearch,downContact,modifyContact,modifyContactHandle,addContact,addContactHandle,deleteContact,
						,depart_index,detail,more,
						, addImpworkHandle,impfile,addImpfileHandle,schedule,addScheduleHandle,summary,addSummaryHandle,delete,detail,      
						,askLeaveDetail,quarterDelete,judgeGatherManage,deleteQuarterJudgeItem,deleteCheckAttItem,askLeaveManage,deleteAskLeaveItem,
						,detail,work_manage,workManage_detail,deleteWork,
						,moreNews,
						,dutyUrgentManage,dutyUrgentDetail,dutyUrgentDelete,dutyUrgentSearch,dutyUrgentDownload,
						,deletePersonnelInfoItem,modifyPersonnelInfoItem,
						,modifyItemClass,deleteItemClass,downContact,
						,study_index,view,manage_detail,deleteMeet,
						,study_index,view,manage_detail,deleteStudy,
						,inspect_manage,deleteInspect,add_inspect,modify_inspect,
						,addRoleHandle,addNodeHandle,deleteNode,alterNode,alterNodeHandle,access,setAccess,alterUser,alterUserHandle,
						
						,dutyGather_detail,dutyGather_modify,dutyGather_confirm,dutyGather_delete,
						,detail_outfile,excel_outfile,file_excel,
						,target_set,saveProjectHandle,fillProject,fillProjectHandle,saveContrast,separate_set,saveSeparateHandle,fillSepProject,fillSepProjectHandle,saveContrast2,modifyleader,modifyleaderHandle,target_modify,modifyProject,modifyProjectHandle,deleteProject,separate_modify,modifyProject_sep,modifyProjectHandle_sep,deleteProject_sep,
						,download_outmeet,deleteOneMessage,deleteGroupMessage,
						,modifyCheckAtt,personnelInfo_excel,
						,addPetition_index,confirmAccept,done_excel,ask_word,addPetition_index,
						,contactUs,copyright,manageMeetPlace,addMeetPlaceHandle,deleteMeetPlace,modifyMeetPlace,modifyMeetPlaceHandle,
						,addDep,addDepHandle,deleteDep,modifyDep,modifyDepHandle,
						,excel_vehicle_1,excel_vehicle_2,excel_vehicle_3,excel_stock,excel_item,excel_maintain,
						,meet_look,
						,addProject,addProjectHandle,addProject_sep,addProjectHandle_sep,
						,closeWindow,modifyReported,
						,check2,
						,approveDutyUrgent,outfile_excel,
						,download_exists,
						,modify_meet,',//无需验证的方法
	//自己写的一个配置项 用来精细验证,
	'REQUIRE_AUTH_MODULE_ACTION'=>'Depart_index,Notify_index',

	'RBAC_ROLE_TABLE'=>'role',//角色表
	'RBAC_USER_TABLE'=>'role_user',//角色用户中间表
	'RBAC_ACCESS_TABLE'=>'access',
	'RBAC_NODE_TABLE'=>'node',
		);
?>