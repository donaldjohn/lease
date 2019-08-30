<?php
namespace app\modules\auth;


class ModuleRouter extends \app\modules\ModuleRouter
{
    public function initialize()
    {
        //新增业务API
        $this->addRoutes("/busapi",array(
            "GET" => array("controller" => "busapi", "action" => "list"),
            "POST" => array("controller" => "busapi", "action" => "create"),
        ));
        $this->addRoutes("/busapi/{id:[0-9]+}",array(
            "GET" => array("controller" => "busapi", "action" => "one"),
            "PUT" => array("controller" => "busapi", "action" => "update"),
            "DELETE" => array("controller" => "busapi", "action" => "delete"),
        ));
        $this->addRoutes("/busapi/order",array(
            "GET" => array("controller" => "busapi", "action" => "order")
        ));

        //功能模块
        $this->addRoutes("/func",array(
            "GET" => array("controller" => "func", "action" => "list"),
            "POST" => array("controller" => "func", "action" => "create"),
        ));
        $this->addRoutes("/func/{id:[0-9]+}",array(
            "GET" => array("controller" => "func", "action" => "one"),
            "PUT" => array("controller" => "func", "action" => "update"),
            "DELETE" => array("controller" => "func", "action" => "delete"),
        ));
        $this->addRoutes("/func/order",array(
            "GET" => array("controller" => "func", "action" => "order")
        ));


        //用户API
        $this->addRoutes("/user",array(
            "GET" => array("controller" => "user", "action" => "list"),
            "POST" => array("controller" => "user", "action" => "create"),
        ));
        $this->addRoutes("/user/{id:[0-9]+}",array(
            "GET" => array("controller" => "user", "action" => "one"),
            "PUT" => array("controller" => "user", "action" => "update"),
            "DELETE" => array("controller" => "user", "action" => "delete"),
        ));
        $this->addRoutes("/user/{id:[0-9]+}/restpwd",array(
            "PUT" => array("controller" => "user", "action" => "restpwd"),
        ));
        $this->addRoutes("/user/pwd",array(
            "PUT" => array("controller" => "user", "action" => "changepwd"),
        ));


        //角色模块api
        $this->addRoutes("/role",array(
            "GET" => array("controller" => "role", "action" => "list"),
            "POST" => array("controller" => "role", "action" => "create"),
        ));
        $this->addRoutes("/role/{id:[0-9]+}",array(
            "GET" => array("controller" => "role", "action" => "one"),
            "PUT" => array("controller" => "role", "action" => "update"),
            "DELETE" => array("controller" => "role", "action" => "delete"),
        ));
        $this->addRoutes("/role/{id:[0-9]+}/tree",array(
            "GET" => array("controller" => "role", "action" => "tree"),
            "PUT" => array("controller" => "role", "action" => "auth")

        ));
        $this->addRoutes("/role/{id:[0-9]+}/rolemenu",array(
            "GET" => array("controller" => "role", "action" => "rolemenu"),
        ));

        //用户组模块api
        $this->addRoutes("/usergroup",array(
            "GET" => array("controller" => "usergroup", "action" => "list"),
            "POST" => array("controller" => "usergroup", "action" => "create"),
        ));
        $this->addRoutes("/usergroup/code",array(
            "GET" => array("controller" => "usergroup", "action" => "code"),
        ));
        $this->addRoutes("/usergroup/{id:[0-9]+}",array(
            "GET" => array("controller" => "usergroup", "action" => "one"),
            "PUT" => array("controller" => "usergroup", "action" => "update"),
            "DELETE" => array("controller" => "usergroup", "action" => "delete"),
        ));

        //type
        $this->addRoutes("/type",array(
            "GET" => array("controller" => "type", "action" => "list"),
        ));

        //子系统
        $this->addRoutes("/sub",array(
            "GET" => array("controller" => "sub", "action" => "list"),
            "POST" => array("controller" => "sub", "action" => "create"),
        ));
        $this->addRoutes("/sub/{id:[0-9]+}",array(
            "GET" => array("controller" => "sub", "action" => "one"),
            "PUT" => array("controller" => "sub", "action" => "update"),
            "DELETE" => array("controller" => "sub", "action" => "delete"),
        ));

        //功能点模块
        $this->addRoutes("/func/{funcId:[0-9]+}/point",array(
            "GET" => array("controller" => "point", "action" => "list"),
            "POST" => array("controller" => "point", "action" => "create"),
        ));
        $this->addRoutes("/func/{funcId:[0-9]+}/point/{id:[0-9]+}",array(
            "GET" => array("controller" => "point", "action" => "one"),
            "PUT" => array("controller" => "point", "action" => "update"),
            "DELETE" => array("controller" => "point", "action" => "delete"),
        ));


        //所有功能树列表
        $this->addRoutes("/usergroup/{id:[0-9]+}/tree",array(
            "GET" => array("controller" => "usergroup", "action" => "tree"),
        ));
        $this->addRoutes("/usergroup/{id:[0-9]+}/groupmenu",array(
            "GET" => array("controller" => "usergroup", "action" => "groupmenu"),
        ));

        //更新用户组功能列表
        $this->addRoutes("/usergroup/{id:[0-9]+}/tree",array(
            "PUT" => array("controller" => "usergroup", "action" => "auth"),
        ));
        $this->addRoutes("/usergroup/{id:[0-9]+}/sub",array(
            "GET" => array("controller" => "sub", "action" => "usergroup"),
            "PUT" => array("controller" => "sub", "action" => "updateusergroup"),
        ));
        $this->addRoutes("/role/{id:[0-9]+}/sub",array(
            "GET" => array("controller" => "sub", "action" => "role"),
            "PUT" => array("controller" => "sub", "action" => "updaterole"),
        ));

        //邮管局管理
        $this->addRoutes("/postoffice",array(
            "GET" => array("controller" => "postoffice", "action" => "list"),
            "POST" => array("controller" => "postoffice", "action" => "create"),
        ));
        $this->addRoutes("/postoffice/{id:[0-9]+}",array(
            "GET" => array("controller" => "postoffice", "action" => "one"),
            "PUT" => array("controller" => "postoffice", "action" => "update"),
            "DELETE" => array("controller" => "postoffice", "action" => "delete"),
        ));

        //配送企业管理
        $this->addRoutes("/dispathing",array(
            "GET" => array("controller" => "dispathing", "action" => "list"),
            "POST" => array("controller" => "dispathing", "action" => "create"),
        ));
        $this->addRoutes("/dispathing/{id:[0-9]+}",array(
            "GET" => array("controller" => "dispathing", "action" => "one"),
            "PUT" => array("controller" => "dispathing", "action" => "update"),
            "DELETE" => array("controller" => "dispathing", "action" => "delete"),
        ));

        //快递协会管理
        $this->addRoutes("/association",array(
            "GET" => array("controller" => "association", "action" => "list"),
            "POST" => array("controller" => "association", "action" => "create"),
        ));
        $this->addRoutes("/association/{id:[0-9]+}",array(
            "GET" => array("controller" => "association", "action" => "one"),
            "PUT" => array("controller" => "association", "action" => "update"),
            "DELETE" => array("controller" => "association", "action" => "delete"),
        ));


        //保险公司管理
        $this->addRoutes("/insurer",array(
            "GET" => array("controller" => "insurer", "action" => "list"),
            "POST" => array("controller" => "insurer", "action" => "create"),
        ));
        $this->addRoutes("/insurer/{id:[0-9]+}",array(
            "GET" => array("controller" => "insurer", "action" => "one"),
            "PUT" => array("controller" => "insurer", "action" => "update"),
            "DELETE" => array("controller" => "insurer", "action" => "delete"),
        ));
        $this->addRoutes("/insurer/list",array(
            "GET" => array("controller" => "insurer", "action" => "list2"),
        ));



        //门店管理
        $this->addRoutes("/store",array(
            "GET" => array("controller" => "store", "action" => "list"),
            "POST" => array("controller" => "store", "action" => "create"),
        ));
        $this->addRoutes("/store/{id:[0-9]+}",array(
            "GET" => array("controller" => "store", "action" => "one"),
            "PUT" => array("controller" => "store", "action" => "update"),
            "DELETE" => array("controller" => "store", "action" => "delete"),
        ));


        //供应商管理
        $this->addRoutes("/supplier",array(
            "GET" => array("controller" => "supplier", "action" => "list"),
            "POST" => array("controller" => "supplier", "action" => "create"),
        ));
        $this->addRoutes("/supplier/{id:[0-9]+}",array(
            "GET" => array("controller" => "supplier", "action" => "one"),
            "PUT" => array("controller" => "supplier", "action" => "update"),
            "DELETE" => array("controller" => "supplier", "action" => "delete"),
        ));
        $this->addRoutes("/supplier/list",array(
            "GET" => array("controller" => "supplier", "action" => "list2"),
        ));

        //快递公司管理
        $this->addRoutes("/express",array(
            "GET" => array("controller" => "express", "action" => "list"),
            "POST" => array("controller" => "express", "action" => "create"),
        ));
        $this->addRoutes("/express/{id:[0-9]+}",array(
            "GET" => array("controller" => "express", "action" => "one"),
            "PUT" => array("controller" => "express", "action" => "update"),
            "DELETE" => array("controller" => "express", "action" => "delete"),
        ));

        $this->addRoutes("/express/list",array(
            "GET" => array("controller" => "express", "action" => "list2"),
        ));

        //子用户API
        $this->addRoutes("/subuser",array(
            "GET" => array("controller" => "subuser", "action" => "list"),
            "POST" => array("controller" => "subuser", "action" => "create"),
        ));
        $this->addRoutes("/subuser/{id:[0-9]+}",array(
            "GET" => array("controller" => "subuser", "action" => "one"),
            "PUT" => array("controller" => "subuser", "action" => "update"),
            "DELETE" => array("controller" => "subuser", "action" => "delete"),
        ));
        $this->addRoutes("/subuser/{id:[0-9]+}/restpwd",array(
            "PUT" => array("controller" => "subuser", "action" => "restpwd"),
        ));
        $this->addRoutes("/subuser/pwd",array(
            "PUT" => array("controller" => "subuser", "action" => "changepwd"),
        ));



        $this->addRoutes("/menu", array(
            "GET" => array("controller" => "menu", "action" => "index")
        ));


        $this->addRoutes("/trafficpolice",array(
            "GET" => array("controller" => "trafficpolice", "action" => "list"),
            "POST" => array("controller" => "trafficpolice", "action" => "create"),
        ));
        $this->addRoutes("/trafficpolice/{id:[0-9]+}",array(
            "PUT" => array("controller" => "trafficpolice", "action" => "update"),
            "DELETE" => array("controller" => "trafficpolice", "action" => "delete"),
        ));
        $this->addRoutes("/trafficpolice/self",array(
            "GET" => array("controller" => "trafficpolice", "action" => "self"),
        ));
        $this->addRoutes("/trafficpolice/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "trafficpolice", "action" => "status")
        ));

        $this->addRoutes("/police-man",array(
            "GET" => array("controller" => "policeman", "action" => "list"),
            "POST" => array("controller" => "policeman", "action" => "create"),
        ));
        $this->addRoutes("/police-man/{id:[0-9]+}",array(
            "PUT" => array("controller" => "policeman", "action" => "update"),
            "DELETE" => array("controller" => "policeman", "action" => "delete"),
        ));

        $this->addRoutes("/police-man/{id:[0-9]+}/status",array(
            "PUT" => array("controller" => "policeman", "action" => "status"),
        ));
        $this->addRoutes("/role-list",array(
            "GET" => array("controller" => "role", "action" => "rlist"),
        ));
        $this->addRoutes("/AreaInfo",array(
            "GET" => array("controller" => "User", "action" => "AreaInfo"),
        ));
        $this->add('[/]?');
    }
}