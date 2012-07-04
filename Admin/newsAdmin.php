<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="<?= $GLOBALS['BASE_URL'] ?>Styles/main.css" type="text/css">

    <style>
    	.add-news { background-image: url(<?= $GLOBALS['BASE_URL'] ?>Javascripts/ext/resources/images/default/dd/drop-add.gif) !important; }
    	.del-news { background-image: url(<?= $GLOBALS['BASE_URL'] ?>Javascripts/ext/resources/images/default/dd/drop-no.gif) !important; }
    </style>

    <!-- ext stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?= $GLOBALS['BASE_URL'] ?>Javascripts/ext/resources/css/ext-all.css" />

    <!-- ext libraries -->
		<script type="text/javascript" src="<?= $GLOBALS['BASE_URL'] ?>Javascripts/ext/adapter/ext/ext-base.js"></script>
		<script type="text/javascript" src="<?= $GLOBALS['BASE_URL'] ?>Javascripts/ext/ext-all.js"></script>

    <script type="text/javascript">

Ext.onReady(function() {

	// deletes a News article by making an Ajax call to the backend.
	// First it's displaying an confirmation dialog box.
	// After deletion it's resetting the form, reloading the tree and disabling the delete button
	function deleteNews(idToDelete, headline) {
		Ext.MessageBox.confirm('Delete News', 'Are you sure that you want to delete the News <b>"' + headline + '"</b>?', function(btn){
			if (btn == 'yes') {
				Ext.Ajax.request({
					url: '<?= $GLOBALS['BASE_URL'] ?>Backend/deleteNews.php',
					failure: function() {
						Ext.Msg.alert('Failure', 'Unable to remove News');
					},
					params: { 'id': idToDelete }
				});
				newsAdminFormPanel.getForm().reset();
				newsAdminNewsList.getLoader().load(newsAdminNewsList.getRootNode(), null, null);
				emptyTab.show();
				newsAdminFormPanel.hide();
				Ext.getCmp('delNews').disable();
			}
		});
	}

	// emptyTab, shown if no News Article is selected
	var emptyTab = new Ext.Panel({
		id: 'emptyTab',
		name: 'emptyTab',
		frame: true,
		html: 'no item selected'
	});

	// the form panel for editing a News
	var newsAdminFormPanel = new Ext.FormPanel({
		id: 'newsAdminFormPanel',
		name: 'newsAdminFormPanel',
		hidden: true,
		labelAlign: 'top',
    frame: true,
    title: 'Editing News',
    bodyStyle:'padding:5px 5px 0',
    reader : new Ext.data.JsonReader({
    	root : '',
    	fields: ['id','headline','html']
    }),
    items: [{
    	xtype: 'hidden',
    	name: 'id',
    	id: 'id'
      },{
    	layout:'column',
      items:[{
      columnWidth:1,
      layout: 'form',
      items: [{
      	xtype:'textfield',
        fieldLabel: 'Headline',
        name: 'headline',
        allowBlank:false,
        anchor:'98%'
      }]
      }]
      },{
      xtype:'htmleditor',
      id:'content',
      name: 'html',
      fieldLabel:'Content',
      height:200,
      anchor:'98%',
      allowBlank:false
      }]
    });

    var newsAdminNewsList = new Ext.tree.TreePanel({
    	id: 'newsAdminNewsList',
    	name: 'newsAdminNewsList',
      tbar: [{
            iconCls:'add-news',
            text:'Add News',
            scope: this,
            handler: function() {
            	emptyTab.hide();
            	newsAdminFormPanel.show();
            	newsAdminFormPanel.getForm().reset();
            	var newNode = new Ext.tree.TreeNode({
            		leaf: true,
            		text: '&lt;new news&gt;',
            		id: -1
            	});
            	newsAdminNewsList.getRootNode().insertBefore(newNode, newsAdminNewsList.getRootNode().item(0));
            	newNode.select();
            }
        },{
        	  id: 'delNews',
            iconCls:'del-news',
            text:'Remove News',
            scope: this,
            disabled: true,
            handler: function() {
            	if (newsAdminNewsList.getSelectionModel().getSelectedNode()) {
            		deleteNews(newsAdminNewsList.getSelectionModel().getSelectedNode().id, newsAdminNewsList.getSelectionModel().getSelectedNode().text);
            	} else {
            		Ext.Msg.alert('', 'Nothing selected');
            }
          }
        }],
      loader: new Ext.tree.TreeLoader({
      	url: '<?= $GLOBALS['BASE_URL'] ?>Backend/loadNews.php?asExtTreeNode=true',
      	clearOnLoad: true
      }),
    	root: {
    		nodeType: 'async',
    		text: 'News',
    		draggable: false,
    		id: 'source'
    	},
    	rootVisible: false,
    	border: false,
    	autoscroll: true,
    	listeners: {
    		click: function(n) {
    			emptyTab.hide();
    			Ext.getCmp('delNews').enable();
    			newsAdminFormPanel.show();
    			newsAdminFormPanel.getForm().load({url:'<?= $GLOBALS['BASE_URL'] ?>Backend/loadNews.php?id=' + n.attributes.id, waitMsg:'Loading...'});
    		}
      }
    });

    newsAdminNewsList.getRootNode().expand();

    var mainTabPanel = new Ext.TabPanel({
    	id: 'mainTabPanel',
    	name: 'mainTabPanel',
    	width: 900,
    	height: 500,
    	activeTab: 0,
    	autoscroll: true,
    	items: [{
    		title: 'News Administration',
    		layout: 'border',
    		items: [{
        // xtype: 'panel' implied by default
        region:'west',
        margins: '5 0 0 5',
        width: 200,
        collapsible: true,   // make collapsible
        cmargins: '5 5 0 5', // adjust top margin when collapsed
        id: 'west-region-container',
        layout: 'fit',
        items: [newsAdminNewsList],
        split: true,
        autoscroll: true
    },{
        region: 'center',     // center region is required, no width/height specified
        xtype: 'container',
        layout: 'fit',
        margins: '5 5 0 0',
        items: [emptyTab, newsAdminFormPanel],
        autoscroll: true
    }]


        }]
    });


   	newsAdminFormPanel.addButton({
        text: 'Save',
        handler: function(){
            newsAdminFormPanel.getForm().submit(
               {url:'<?= $GLOBALS['BASE_URL'] ?>Backend/saveNews.php',
               	waitMsg:'Saving Data...',
                success: function(form, action) {
                	newsAdminNewsList.getLoader().load(newsAdminNewsList.getRootNode(), null, null);
                	newsAdminFormPanel.getForm().load({url:'<?= $GLOBALS['BASE_URL'] ?>Backend/loadNews.php?id=' + action.result.newId, waitMsg:'Saving Data...'});
                },
                failure: function(form, action) {
                	switch (action.failureType) {
                		case Ext.form.Action.CLIENT_INVALID:
                		Ext.Msg.alert('Failure', 'Required fields are empty!');
                		break;
                		case Ext.form.Action.CONNECT_FAILURE:
                		Ext.Msg.alert('Failure', 'Ajax Error');
                		break;
                		case Ext.form.Action.SERVER_INVALID:
                		Ext.Msg.alert('Failure', action.result.msg);
                	}
               }
               })
         }
      });

      newsAdminFormPanel.addButton({
        text: 'Cancel',
        handler: function() {
        	newsAdminFormPanel.getForm().reset();
        	newsAdminNewsList.getLoader().load(newsAdminNewsList.getRootNode(), null, null);
        	emptyTab.show();
        	newsAdminFormPanel.hide();
        	Ext.getCmp('delNews').disable();
        }
        });

   	mainTabPanel.render(document.getElementById('pageMainContent'));

});

    </script>

  </head>
  <body>

    <div id="pageHeader">
<?php

show_header_logo();

?>
      </div>
    </div>

    <div id="pageMainContent"></div>

  </body>
</html>
