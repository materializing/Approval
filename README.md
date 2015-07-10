Approval
==========
baserCMS用「簡易型 ５段階承認」プラグイン


このプラグインはbaserCMSの固定ページ管理、ブログプラグインにおいて、ページや記事の公開を承認制にできるプラグインです。
最大で５段階（ページや記事の作成者は含まない）までの多段階承認を可能にし、承認権限者にはユーザー個人またはグループを指定できる（グループを指定した場合、そのグループに所属するユーザーなら誰でも承認できる）。


また、固定ページ・ブログともに、カテゴリ毎に異なる承認設定が可能。必要な承認段階や承認権限者を変更することが出来る。


なお、権限者により「承認」または「差戻し」が行われると、「承認」の場合は次段階権限者に、「差戻し」の場合は前段階権限者に通知メールが送信される。なお、承認や差戻しを行う際に「申し送り事項」を記入でき、そこに入力されたテキストは通知メールに記載される。


ただし、あくまでも「簡易型」としての機能しか持ちあわせておらず、記事のバージョン管理のような事は出来ない。そのため、一旦公開された記事（最終権限者に承認を得た記事）を、下位権限者が編集したい場合は、最終権限者が記事を「非公開」としたうえ、さらに下位権限者まで「差戻し」を行う必要がある。


Last Version
-------
0.9.0


System Requirements
-------
baserCMS 3.0.7


License
-------
MIT

このプラグインはMITライセンスのもと無償でご利用いただけます。


Copyright 2015, Hiniarata co.,Ltd.
