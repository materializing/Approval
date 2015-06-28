<?php
/*
* 承認プラグイン
* 承認フォーム生成 イベントリスナー
*
* PHP 5.4.x
*
* @copyright    Copyright (c) hiniarata co.ltd
* @link         https://hiniarata.jp
* @package      Approval Plugin Project
* @since        ver.0.9.0
*/

/**
* ヘルパー イベントリスナー
*
* @package baser.plugins.approval
*/
class ApprovalHelperEventListener extends BcHelperEventListener {

  /**
  * イベント
  *
  * @var array
  * @access public
  */
  public $events = array(
      'Form.afterCreate',
      'Form.afterForm'
  );


  /**
  * メッセージ出力
  * 承認設定がある場合に必要なメッセージを出力する。
  *
  * @return  void
  * @access  public
  */
  public function formAfterCreate(CakeEvent $event){
    //ブログの編集フォームのみで実行する。
    if($event->data['id'] == 'BlogPostForm') {
      //Viewのデータを取得する。
      $View = $event->subject();
      //新規作成の時はスルーする。
      if ($View->view != 'admin_add') {
        //ブログのコンテンツIDを取得する。
        $blogContentId = $View->request->params['pass'][0];
        //承認レベル設定モデルを取得する。
        App::import('Model', 'Approval.ApprovalLevelSetting');
        $approvalLevelSetting = new ApprovalLevelSetting();
        $settingData = $approvalLevelSetting->find('first', array('conditions' => array(
          'blog_content_id' => $blogContentId
        )));

        //設定がされていないブログならスルーする。
        if (!empty($settingData)) {
          //セッション情報からログイン中のユーザー情報を取得する。
          App::uses('CakeSession', 'Model/Datasource');
          $Session = new CakeSession();
          $user = $Session->read('Auth.User');

          //承認記事を確認する。
          App::import('Model', 'Approval.ApprovalPost');
          $approvalPost = new ApprovalPost();
          //承認情報・承認待ちモデルからデータを探す
          $approvalPostData = $approvalPost->find('first',array('conditions' => array(
            'ApprovalPost.post_id' => $View->request->params['pass'][1]
          )));

          //データが存在すれば処理にはいる（基本的にはあるはず。）
          if (!empty($approvalPostData)) {
            $nowStage = $approvalPostData['ApprovalPost']['pass_stage']+1;
            $nowApprovalType = $settingData['ApprovalLevelSetting']['level'.$nowStage.'_type'];
            $nowApprovalId = $settingData['ApprovalLevelSetting']['level'.$nowStage.'_approver_id'];
            $last_approver_id = $settingData['ApprovalLevelSetting']['level'.$settingData['ApprovalLevelSetting']['last_stage'].'_approver_id'];

            //通過レベルと最終レベルが一致していれば、最後まで来ている。
            if ($approvalPostData['ApprovalPost']['pass_stage'] == $settingData['ApprovalLevelSetting']['last_stage']) {
              //もしも自分が最終権限者でなければメッセージ
              if ($last_approver_id != $user['id']) {
                $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">この記事は最終権限者の承認を得ています。<br><span style="font-size:13px;color:#666;font-weight:normal;">変更するには権限者からの差し戻しが必要です。</spam></div></div>';
                $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);

              } else {
                $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">この記事は最終承認済みです。<br><span style="font-size:13px;color:#666;font-weight:normal;">差し戻すことで、下位権限者でも編集が出来るようになります（その間、記事は非公開になります）。</span></div></div>';
                $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
              }
            }

            //承認者IDが0の時は、一度申請されて差し戻されたもの。
            if ($approvalPostData['ApprovalPost']['next_approver_id'] == 0){
              $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">この記事を公開するには権限者の承認が必要です。<br><span style="font-size:13px;color:#666;font-weight:normal;">（まだ承認申請されていません。誰でも編集できますが「公開」は出来ません。）</span></div></div>';
              $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
            }

            //承認権限者のタイプを確認する。
            switch($nowApprovalType){
              case 'user':
                //ログインユーザーと権限者の不一致
                if ($nowApprovalId != $user['id']) {
                  //権限者でない場合、警告メッセージと共にactionの先を変更しておく。
                  $message = '<div id="MessageBox"><div id="flashMessage" class="alert-message">現在、この記事は承認申請中です。<br><span style="font-size:13px;color:#666;font-weight:normal;">申請が承認または拒否されるまで承認権限者のみ編集できます。</span></div></div>';
                  $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);

                //承認権限がある。
                } else {
                  //権限者であることを表示する。
                  $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">現在、この記事はあなたの承認を求めています。</div></div>';
                  $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
                }
                break;

              case 'group':
                //ログインユーザーと権限者の不一致
                if ($nowApprovalId != $user['UserGroup']['id']) {
                  //権限者でない場合、警告メッセージと共にactionの先を変更しておく。
                  $message = '<div id="MessageBox"><div id="flashMessage" class="alert-message">現在、この記事は承認申請中です。<br><span style="font-size:13px;color:#666;font-weight:normal;">申請が承認または拒否されるまで承認権限者のみ編集できます。</span></div></div>';
                  $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);

                //承認権限がある。
                } else {
                  //権限者であることを表示する。
                  $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">現在、この記事はあなたが属するグループの承認を求めています。<br><span style="font-size:13px;color:#666;font-weight:normal;">この記事に対する承認は、当該グループに属するユーザーなら誰でも可能です。</span></div></div>';
                  $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
                }
                break;
            }

          //承認情報DBにない場合は、途中で承認設定がついた場合。メッセージを出力する。
          } else {
            $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">この記事を公開するには権限者の承認が必要です。<br><span style="font-size:13px;color:#666;font-weight:normal;">（まだ承認申請されていません。誰でも編集できますが「公開」は出来ません。）</span></div></div>';
            $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
          }
        }
      //新規登録時にメッセージを出力しておく。
      } else {
        $message = '<div id="MessageBox"><div id="flashMessage" class="notice-message">この記事を公開するには権限者の承認が必要です。<br><span style="font-size:13px;color:#666;font-weight:normal;">（まだ承認申請されていません。誰でも編集できますが「公開」は出来ません。）</span></div></div>';
        $event->data['out'] = str_replace ('<form', $message.'<form', $event->data['out']);
      }
    }
    //値を返す
    return $event->data['out'];
  }


  /**
  * 承認選択フォーム出力
  * 承認設定がある場合に必要なメッセージを出力する。
  *
  * @return  void
  * @access  public
  */
  public function formAfterForm(CakeEvent $event) {
    //TODO 承認待ち（pass_stageが自分のレベルより上になってる時）など保存ボタンを消す必要あり。
    //処理を実行するのは、ブログと固定ページ
    if($event->data['id'] == 'BlogPostForm') {
      // BcFormHelperを利用する為、Viewを取得
      $View = $event->subject();
      //ブログのコンテンツIDを取得する。
      $blogContentId = $View->request->params['pass'][0];

      //承認レベル設定モデルを取得する。
      App::import('Model', 'Approval.ApprovalLevelSetting');
      $approvalLevelSetting = new ApprovalLevelSetting();
      $settingData = $approvalLevelSetting->find('first', array('conditions' => array(
        'blog_content_id' => $blogContentId
      )));

      //セッション情報からログイン中のユーザー情報を取得する。
      App::uses('CakeSession', 'Model/Datasource');
      $Session = new CakeSession();
      $user = $Session->read('Auth.User');

      //設定情報を確認する。
      if (!empty($settingData)) {
        //新規作成の場合は、保留か承認申請かに内容を変更する。
        //TODO さし戻ってきたものは、編集だけど「申請」になる。pass_stageで判定する。
        if ($View->request->params['action'] == 'admin_add') {
          $options = array(0 => '保留', 3 => '承認を申請する');
          //承認設定のある新規作成は誰でも書けるので、常に表示する。
          $event->data['fields'][] = array(
              'title'    => '承認設定',
              'input'    =>
              $View->BcForm->input('Approval.approval_flag',
                array(
                  'type' => 'select',
                  'options' => $options
              ))
          );

        //編集（edit）の場合
        } else {
          //現在の承認済段階を確認する。
          //承認情報・承認待ちモデルを取得する。
          App::import('Model', 'Approval.ApprovalPost');
          $approvalPost = new ApprovalPost();
          //承認情報・承認待ちモデルからデータを探す
          $approvalPostData = $approvalPost->find('first',array('conditions' => array(
            'ApprovalPost.post_id' => $View->request->params['pass'][1]
          )));

          //データがある場合
          if (!empty($approvalPostData)) {
            //通過済みの承認ステージ
            $pass_stage = $approvalPostData['ApprovalPost']['pass_stage'];
            if(empty($pass_stage)){
              $pass_stage = 0;
            }
            //現在のステージを確認する（最終承認後はそれ以上ない）
            if ($approvalPostData['ApprovalPost']['pass_stage'] == $settingData['ApprovalLevelSetting']['last_stage']) {
              $nowStage = $approvalPostData['ApprovalPost']['pass_stage'];
            } else {
              $nowStage = $approvalPostData['ApprovalPost']['pass_stage']+1;
            }
            //現在の承認権限者タイプ
            $nowApprovalType = $settingData['ApprovalLevelSetting']['level'.$nowStage.'_type'];

            //編集モードの場合でも、next_approver_idが0の時は、却下で戻ってきたもの。
            if ($pass_stage == 0) {
              //権限者の確認
              switch ($nowApprovalType) {
                case 'user':
                  if ($approvalPostData['ApprovalPost']['next_approver_id'] == $user['id']) {
                    //承認権限があるかどうかを判定する。
                    $now_stage = $pass_stage+1;
                    //ユーザー情報を確認する。
                    $options = array(0 => '保留', 1 => '承認する', 2 => '差し戻す');
                    $event->data['fields'][] = array(
                        'title'    => '承認設定',
                        'input'    =>
                        $View->BcForm->input('Approval.approval_flag',
                          array(
                            'type' => 'select',
                            'options' => $options
                        ))
                    );
                  } else {
                    //自分が権限者でない場合
                    //却下で戻ってきたものの場合は、改めて申請できるので表示する。
                    if ($approvalPostData['ApprovalPost']['next_approver_id'] == 0) {
                      $options = array(0 => '保留', 3 => '承認を申請する');
                      //最初の段階まで戻っていれば、誰もが申請できる。
                      $event->data['fields'][] = array(
                          'title'    => '承認設定',
                          'input'    =>
                          $View->BcForm->input('Approval.approval_flag',
                            array(
                              'type' => 'select',
                              'options' => $options
                          ))
                      );
                    }
                  }
                break;

                case 'group':
                    if ($approvalPostData['ApprovalPost']['next_approver_id'] == $user['UserGroup']['id']) {
                      //承認権限があるかどうかを判定する。
                      $now_stage = $pass_stage+1;
                      //ユーザー情報を確認する。
                      $options = array(0 => '保留', 1 => '承認する', 2 => '差し戻す');
                      $event->data['fields'][] = array(
                          'title'    => '承認設定',
                          'input'    =>
                          $View->BcForm->input('Approval.approval_flag',
                            array(
                              'type' => 'select',
                              'options' => $options
                          ))
                      );
                    } else {
                      //自分が権限者でない場合
                      //却下で戻ってきたものの場合は、改めて申請できるので表示する。
                      if ($approvalPostData['ApprovalPost']['next_approver_id'] == 0) {
                        $options = array(0 => '保留', 3 => '承認を申請する');
                        //最初の段階まで戻っていれば、誰もが申請できる。
                        $event->data['fields'][] = array(
                            'title'    => '承認設定',
                            'input'    =>
                            $View->BcForm->input('Approval.approval_flag',
                              array(
                                'type' => 'select',
                                'options' => $options
                            ))
                        );
                      }
                    }
                  break;

                default:
                  # code...
                  break;
              }

            //却下または第１段階ではなく、承認待ちの状態の場合
            } else {
              $now_stage = $pass_stage+1;
              //権限者の確認
              switch ($nowApprovalType) {
                case 'user':
                  //権限者の確認
                  if ($approvalPostData['ApprovalPost']['next_approver_id']  == $user['id']) {
                    //もしも最終段階まで来ていれば「承認する」はいらない。
                    if ($approvalPostData['ApprovalPost']['pass_stage'] == $settingData['ApprovalLevelSetting']['last_stage']) {
                      $options = array(0 => '承認状態を維持', 2 => '差し戻す');
                    } else {
                      $options = array(0 => '保留', 1 => '承認する', 2 => '差し戻す');
                    }
                    $event->data['fields'][] = array(
                        'title'    => '承認設定',
                        'input'    =>
                        $View->BcForm->input('Approval.approval_flag',
                          array(
                            'type' => 'select',
                            'options' => $options
                        ))
                    );
                  }
                break;
                case 'group':
                  //権限者の確認
                  if ($approvalPostData['ApprovalPost']['next_approver_id']  == $user['UserGroup']['id']) {
                    //もしも最終段階まで来ていれば「承認する」はいらない。
                    if ($approvalPostData['ApprovalPost']['pass_stage'] == $settingData['ApprovalLevelSetting']['last_stage']) {
                      $options = array(0 => '承認状態を維持', 2 => '差し戻す');
                    } else {
                      $options = array(0 => '保留', 1 => '承認する', 2 => '差し戻す');
                    }
                    $event->data['fields'][] = array(
                        'title'    => '承認設定',
                        'input'    =>
                        $View->BcForm->input('Approval.approval_flag',
                          array(
                            'type' => 'select',
                            'options' => $options
                        ))
                    );
                  }
                break;
              }
            }

          //データがない場合は、新規作成後に承認機能を付与した場合。
          } else {
            $options = array(0 => '保留', 3 => '承認を申請する');
            //データがないため新規作成と同じく、だれにでも表示する。
            $event->data['fields'][] = array(
                'title'    => '承認設定',
                'input'    =>
                $View->BcForm->input('Approval.approval_flag',
                  array(
                    'type' => 'select',
                    'options' => $options
                ))
            );
          }
        }
      }
    }
    return true;
  }




}