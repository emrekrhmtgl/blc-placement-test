<?php
/**
 * Plugin Name: BLC Placement Test
 * Description: Independent English placement test with optional post-result lead capture.
 * Version: 1.1.2
 * Requires PHP: 7.4
 * Text Domain: blc-placement-test
 */
if (!defined('ABSPATH')) exit;
// Conditional declaration also defers PHP class registration until this check runs.
if (!class_exists('BLC_Placement_Test', false)) {
final class BLC_Placement_Test {
 const VER='1.1.2'; const TABLE='blc_pt_questions'; const ATTEMPTS='blc_pt_attempts'; const LEADS='blc_pt_leads';
 public static function init(){
  register_activation_hook(__FILE__,[__CLASS__,'activate']);
  register_deactivation_hook(__FILE__,[__CLASS__,'deactivate']);
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('blc_pt_daily_cleanup',[__CLASS__,'cleanup']);
  add_action('admin_post_blc_pt_save_question',[__CLASS__,'save_question']);
  add_action('admin_post_blc_pt_delete_question',[__CLASS__,'delete_question']);
  add_action('admin_post_blc_pt_delete_lead',[__CLASS__,'delete_lead']);
  add_action('wp_enqueue_scripts',[__CLASS__,'register_assets']);
  add_shortcode('blc_placement_test',[__CLASS__,'shortcode']);
  add_shortcode('blc_placement_test_page',[__CLASS__,'page_shortcode']);
  foreach(['get_test','submit','lead'] as $method){
   add_action('wp_ajax_blc_pt_'.$method,[__CLASS__,$method]);
   add_action('wp_ajax_nopriv_blc_pt_'.$method,[__CLASS__,$method]);
  }
 }
 static function deactivate(){wp_clear_scheduled_hook('blc_pt_daily_cleanup');}
 static function cleanup(){global $wpdb;$wpdb->query($wpdb->prepare('DELETE a FROM '.self::name(self::ATTEMPTS).' a LEFT JOIN '.self::name(self::LEADS).' l ON a.id=l.attempt_id WHERE a.created_at < %s AND l.id IS NULL',date('Y-m-d H:i:s',current_time('timestamp')-30*DAY_IN_SECONDS)));}
 static function throttle($action,$limit){$ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown';$key='blc_pt_'.md5($action.'|'.$ip);$count=(int)get_transient($key);if($count>=$limit)wp_send_json_error(['message'=>'Çok fazla istek gönderdiniz. Lütfen daha sonra tekrar deneyin.'],429);set_transient($key,$count+1,HOUR_IN_SECONDS);}
 static function name($suffix){global $wpdb;return $wpdb->prefix.$suffix;}
 static function levels(){return ['A1','A2','B1','B2','C1'];}
 static function activate(){
  global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$collate=$wpdb->get_charset_collate();
  dbDelta('CREATE TABLE '.self::name(self::TABLE)." (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   level varchar(2) NOT NULL,
   category varchar(30) NOT NULL DEFAULT 'grammar',
   question text NOT NULL,
   choices longtext NOT NULL,
   correct tinyint(3) unsigned NOT NULL,
   explanation text NOT NULL,
   active tinyint(1) NOT NULL DEFAULT 1,
   PRIMARY KEY  (id),
   KEY level_active (level,active)
  ) $collate;");
  dbDelta('CREATE TABLE '.self::name(self::ATTEMPTS)." (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   token_hash char(64) NOT NULL,
   question_ids longtext NOT NULL,
   finished tinyint(1) NOT NULL DEFAULT 0,
   result_level varchar(8) NOT NULL DEFAULT '',
   scores longtext NULL,
   created_at datetime NOT NULL,
   finished_at datetime NULL,
   PRIMARY KEY  (id),
   UNIQUE KEY token_hash (token_hash),
   KEY created_at (created_at)
  ) $collate;");
  dbDelta('CREATE TABLE '.self::name(self::LEADS)." (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   attempt_id bigint(20) unsigned NOT NULL,
   full_name varchar(190) NOT NULL,
   email varchar(190) NOT NULL DEFAULT '',
   phone varchar(40) NOT NULL DEFAULT '',
   branch varchar(190) NOT NULL DEFAULT '',
   created_at datetime NOT NULL,
   PRIMARY KEY  (id),
   UNIQUE KEY attempt_id (attempt_id)
  ) $collate;");
  if((int)$wpdb->get_var('SELECT COUNT(*) FROM '.self::name(self::TABLE))===0)self::seed();
  if(!wp_next_scheduled('blc_pt_daily_cleanup'))wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','blc_pt_daily_cleanup');
 }
 static function seed(){
  global $wpdb;
  // Level, category, prompt, four answers, zero-indexed correct answer.
  $bank=[
   ['A1','grammar','My sister ___ a teacher.',['am','is','are','be'],1],
   ['A1','grammar','They ___ from Canada.',['is','are','am','be'],1],
   ['A1','grammar','We have ___ orange.',['a','an','any','some'],1],
   ['A1','grammar','He ___ to school every day.',['go','goes','going','gone'],1],
   ['A1','grammar','___ you like music?',['Are','Is','Do','Does'],2],
   ['A1','grammar','There ___ three chairs here.',['is','are','am','be'],1],
   ['A1','vocabulary','Which word is the opposite of “old”?',['young','large','slow','short'],0],
   ['A1','reading','Anna has two brothers and one sister. How many siblings does Anna have?',['One','Two','Three','Four'],2],
   ['A2','grammar','We ___ the museum last Saturday.',['visit','visited','have visit','visiting'],1],
   ['A2','grammar','This bag is ___ than that one.',['heavy','heavier','heaviest','more heavy'],1],
   ['A2','grammar','I do not have ___ money with me.',['many','some','any','a'],2],
   ['A2','grammar','She is good ___ drawing.',['on','at','to','for'],1],
   ['A2','grammar','I ___ dinner when the phone rang.',['cook','cooked','was cooking','have cooked'],2],
   ['A2','grammar','You ___ show your passport at the border. It is required.',['must','might','would','could'],0],
   ['A2','vocabulary','“Purchase” means ___.',['sell','buy','borrow','lose'],1],
   ['A2','reading','The library closes at six. It is now half past five. How much time remains?',['Fifteen minutes','Thirty minutes','One hour','Two hours'],1],
   ['B1','grammar','I have known her ___ 2018.',['for','since','during','from'],1],
   ['B1','grammar','If I had a car, I ___ to work.',['drive','will drive','would drive','drove'],2],
   ['B1','grammar','The letter ___ yesterday.',['sent','was sent','has send','sends'],1],
   ['B1','grammar','He asked me where I ___.',['worked','work','am working','have work'],0],
   ['B1','grammar','I look forward to ___ from you.',['hear','hearing','heard','have heard'],1],
   ['B1','vocabulary','We had to ___ the meeting because the manager was ill.',['put off','look up','take after','get over'],0],
   ['B1','grammar','Although it was raining, they ___ the match.',['cancel','continued','refused','stopped'],1],
   ['B1','reading','Maria missed her flight because the train to the airport was delayed. What caused her to miss it?',['She forgot her ticket.','The train was late.','The flight left early.','She went to the wrong airport.'],1],
   ['B2','grammar','By the time we got there, the concert ___.',['began','has begun','had begun','begins'],2],
   ['B2','grammar','If I ___ earlier, I would have caught the bus.',['leave','had left','have left','would leave'],1],
   ['B2','grammar','She denied ___ the document.',['to sign','sign','signing','signed'],2],
   ['B2','grammar','Hardly ___ we arrived when it started to snow.',['did','have','had','were'],2],
   ['B2','vocabulary','The plan was rejected because it was not financially ___.',['feasible','visible','fluent','reluctant'],0],
   ['B2','grammar','Despite ___ very little preparation, he did well.',['have','having','to have','had'],1],
   ['B2','grammar','The committee recommended that the report ___ revised.',['is','be','was','being'],1],
   ['B2','reading','“The policy may lower costs; however, its long-term effects remain unclear.” The writer is ___.',['certain it will fail','certain it will succeed','cautious about its impact','opposed to all changes'],2],
   ['C1','grammar','Not until the results were published ___ the team realise the error.',['had','did','was','has'],1],
   ['C1','grammar','Had she known, she ___ the invitation.',['declines','would have declined','had declined','will decline'],1],
   ['C1','grammar','The evidence was insufficient, ___ further investigation.',['necessitating','necessitate','necessitated','to necessitated'],0],
   ['C1','vocabulary','The spokesperson tried to ___ public concerns about safety.',['allay','aggravate','conceal','dismiss'],0],
   ['C1','grammar','The offer is contingent ___ final approval.',['at','with','on','for'],2],
   ['C1','grammar','Little ___ how serious the problem would become.',['they realised','did they realise','had they realise','they have realise'],1],
   ['C1','vocabulary','To “substantiate” a claim is to ___.',['reject it','make it shorter','support it with evidence','announce it publicly'],2],
   ['C1','reading','“The initial benefits were modest, and the wider implications have yet to be assessed.” Which conclusion is justified?',['There were no benefits.','The broader impact remains uncertain.','The project definitely failed.','All outcomes have been measured.'],1]
  ];
  foreach($bank as $r){$wpdb->insert(self::name(self::TABLE),['level'=>$r[0],'category'=>$r[1],'question'=>$r[2],'choices'=>wp_json_encode($r[3]),'correct'=>$r[4],'explanation'=>'','active'=>1],['%s','%s','%s','%s','%d','%s','%d']);}
 }
 static function register_assets(){wp_register_style('blc-pt',plugins_url('assets/test.css',__FILE__),[],self::VER);wp_register_script('blc-pt',plugins_url('assets/test.js',__FILE__),[],self::VER,true);}
 static function page_shortcode(){
  self::register_assets();
  wp_enqueue_style('blc-pt-page',plugins_url('assets/page.css',__FILE__),['blc-pt'],self::VER);
  wp_enqueue_script('blc-pt-page',plugins_url('assets/page.js',__FILE__),['blc-pt'],self::VER,true);
  $html=file_get_contents(__DIR__.'/templates/page.html');
  if($html===false)return self::shortcode();
  $html=str_replace('__BLC_EXAM_ID__',esc_attr(wp_unique_id('blc-test-')),$html);
  return do_shortcode($html);
 }
 static function shortcode(){wp_enqueue_style('blc-pt');wp_enqueue_script('blc-pt');wp_localize_script('blc-pt','BLCPT',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('blc_pt_public')]);return '<div class="blc-pt" data-blc-pt><div class="blc-pt-card"><div class="blc-pt-kicker">BRITISH LANGUAGE CENTER</div><div class="blc-pt-app" aria-live="polite"><h2>İngilizce Seviyeni Keşfet</h2><p>40 soruluk ön değerlendirmeye hemen başlayabilirsin. Kişisel bilgi vermek zorunda değilsin.</p><button class="blc-pt-btn" data-start>Teste Başla →</button><p class="blc-pt-fine">Sonuçlar tahminidir; resmî CEFR belgesi veya konuşma/dinleme değerlendirmesi değildir.</p></div></div></div>';}
 static function verify_public(){if(!check_ajax_referer('blc_pt_public','nonce',false))wp_send_json_error(['message'=>'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.'],403);}
 static function request_post($key){return isset($_POST[$key]) && is_string($_POST[$key])?wp_unslash($_POST[$key]):'';}
 static function get_test(){self::verify_public();self::throttle('get_test',12);global $wpdb;
  $selected=[];$out=[];
  foreach(self::levels() as $level){
   $rows=$wpdb->get_results($wpdb->prepare('SELECT id,level,category,question,choices FROM '.self::name(self::TABLE).' WHERE active=1 AND level=%s ORDER BY RAND() LIMIT 8',$level),ARRAY_A);
   if(count($rows)<8)wp_send_json_error(['message'=>'Soru bankası şu anda sınav için yeterli değil.'],503);
   foreach($rows as $r){$choices=json_decode($r['choices'],true);if(!is_array($choices)||count($choices)!==4)wp_send_json_error(['message'=>'Soru verisi eksik.'],503);$selected[]=(int)$r['id'];$out[]=['id'=>(int)$r['id'],'level'=>$r['level'],'category'=>$r['category'],'question'=>$r['question'],'choices'=>array_values($choices)];}
  }
  $token=bin2hex(random_bytes(32));$ok=$wpdb->insert(self::name(self::ATTEMPTS),['token_hash'=>hash('sha256',$token),'question_ids'=>wp_json_encode($selected),'created_at'=>current_time('mysql')],['%s','%s','%s']);
  if(!$ok)wp_send_json_error(['message'=>'Sınav oluşturulamadı.'],500);
  wp_send_json_success(['token'=>$token,'questions'=>$out]);
 }
 static function submit(){self::verify_public();global $wpdb;$token=self::request_post('token');if(!preg_match('/^[a-f0-9]{64}$/',$token))wp_send_json_error(['message'=>'Geçersiz sınav anahtarı.'],400);
  $table=self::name(self::ATTEMPTS);$attempt=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token_hash=%s",hash('sha256',$token)),ARRAY_A);
  if(!$attempt)wp_send_json_error(['message'=>'Sınav bulunamadı.'],404);
  if((int)$attempt['finished']===1)wp_send_json_success(['level'=>$attempt['result_level'],'scores'=>json_decode($attempt['scores'],true),'already_finished'=>true]);
  if(strtotime($attempt['created_at'])<current_time('timestamp')-DAY_IN_SECONDS)wp_send_json_error(['message'=>'Sınav süresi dolmuş. Yeni sınav başlatın.'],410);
  $answers=json_decode(self::request_post('answers'),true);$ids=json_decode($attempt['question_ids'],true);
  if(!is_array($answers)||!is_array($ids)||count($ids)!==40||count($answers)!==40)wp_send_json_error(['message'=>'Lütfen 40 sorunun tamamını yanıtlayın.'],400);
  $score=array_fill_keys(self::levels(),0);
  foreach($ids as $i=>$id){if(!array_key_exists($i,$answers)||!is_int($answers[$i])||$answers[$i]<0||$answers[$i]>3)wp_send_json_error(['message'=>'Geçersiz veya eksik yanıt var.'],400);
   $q=$wpdb->get_row($wpdb->prepare('SELECT level,correct FROM '.self::name(self::TABLE).' WHERE id=%d',(int)$id),ARRAY_A);
   if(!$q||!isset($score[$q['level']]))wp_send_json_error(['message'=>'Soru kaydı bulunamadı.'],500);
   if((int)$q['correct']===$answers[$i])$score[$q['level']]++;
  }
  $level='Pre-A1';foreach(self::levels() as $l){if($score[$l]>=6)$level=$l;else break;}
  $saved=$wpdb->query($wpdb->prepare("UPDATE $table SET finished=1,result_level=%s,scores=%s,finished_at=%s WHERE id=%d AND finished=0",$level,wp_json_encode($score),current_time('mysql'),(int)$attempt['id']));
  if($saved===false)wp_send_json_error(['message'=>'Sonuç kaydedilemedi. Lütfen tekrar deneyin.'],500);
  // Return the persisted result, including when a concurrent submission finished first.
  $persisted=$wpdb->get_row($wpdb->prepare("SELECT result_level,scores FROM $table WHERE id=%d AND finished=1",(int)$attempt['id']),ARRAY_A);
  if(!$persisted)wp_send_json_error(['message'=>'Sonuç okunamadı. Lütfen tekrar deneyin.'],500);
  wp_send_json_success(['level'=>$persisted['result_level'],'scores'=>json_decode($persisted['scores'],true)]);
 }
 static function lead(){self::verify_public();self::throttle('lead',10);global $wpdb;$token=self::request_post('token');if(!preg_match('/^[a-f0-9]{64}$/',$token))wp_send_json_error(['message'=>'Sınav bulunamadı.'],400);
  $attempt=$wpdb->get_row($wpdb->prepare('SELECT id,finished,created_at FROM '.self::name(self::ATTEMPTS).' WHERE token_hash=%s',hash('sha256',$token)),ARRAY_A);
  if(!$attempt||(int)$attempt['finished']!==1)wp_send_json_error(['message'=>'Önce sınavı tamamlayın.'],403);
  if(strtotime($attempt['created_at'])<current_time('timestamp')-7*DAY_IN_SECONDS)wp_send_json_error(['message'=>'Başvuru süresi dolmuş.'],410);
  $name=sanitize_text_field(self::request_post('name'));$email=sanitize_email(self::request_post('email'));$phone=sanitize_text_field(self::request_post('phone'));$branch=sanitize_text_field(self::request_post('branch'));
  $consent=self::request_post('consent');
  if($consent!=='yes'||strlen($name)>570||strlen($name)<2||strlen($phone)>120||strlen($branch)>570||strlen($email)>190||($email!==''&&!is_email($email))||($phone===''&&$email===''))wp_send_json_error(['message'=>'Ad soyad, geçerli telefon veya e-posta ve aydınlatma onayı gereklidir.'],400);
  $saved=$wpdb->insert(self::name(self::LEADS),['attempt_id'=>(int)$attempt['id'],'full_name'=>$name,'email'=>$email,'phone'=>$phone,'branch'=>$branch,'created_at'=>current_time('mysql')],['%d','%s','%s','%s','%s','%s']);
  if(!$saved)wp_send_json_error(['message'=>'Başvuru daha önce gönderilmiş veya kayıt başarısız.'],409);
  wp_send_json_success(['message'=>'Bilgileriniz kaydedildi. Teşekkür ederiz.']);
 }
 static function menu(){add_menu_page('BLC Placement Test','BLC Seviye Testi','manage_options','blc-placement-test',[__CLASS__,'admin_questions'],'dashicons-welcome-learn-more',27);add_submenu_page('blc-placement-test','Başvurular','Başvurular','manage_options','blc-pt-leads',[__CLASS__,'admin_leads']);}
 static function admin_questions(){if(!current_user_can('manage_options'))wp_die('Yetki yok');global $wpdb;$table=self::name(self::TABLE);$edit=isset($_GET['edit'])?absint($_GET['edit']):0;$q=$edit?$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$edit),ARRAY_A):null;$choices=$q?json_decode($q['choices'],true):['','','',''];
  echo '<div class="wrap"><h1>BLC Soru Bankası</h1><p>Sayfaya <code>[blc_placement_test]</code> kısa kodunu ekleyin. Her seviyede en az 8 aktif soru bulunmalıdır.</p><h2>'.($q?'Soruyu Düzenle':'Yeni Soru').'</h2><form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';wp_nonce_field('blc_pt_save');echo '<input type="hidden" name="action" value="blc_pt_save_question"><input type="hidden" name="id" value="'.esc_attr($edit).'"><table class="form-table"><tr><th>Seviye</th><td><select name="level">';foreach(self::levels() as $lv)echo '<option value="'.esc_attr($lv).'" '.selected($q['level']??'A1',$lv,false).'>'.esc_html($lv).'</option>';echo '</select></td></tr><tr><th>Kategori</th><td><select name="category">';foreach(['grammar'=>'Dil bilgisi','vocabulary'=>'Kelime bilgisi','reading'=>'Okuma'] as $key=>$value)echo '<option value="'.esc_attr($key).'" '.selected($q['category']??'grammar',$key,false).'>'.esc_html($value).'</option>';echo '</select></td></tr><tr><th>Soru</th><td><textarea required name="question" rows="3" class="large-text">'.esc_textarea($q['question']??'').'</textarea></td></tr>';foreach($choices as $i=>$choice)echo '<tr><th>Seçenek '.esc_html(chr(65+$i)).'</th><td><input class="regular-text" required name="choices[]" value="'.esc_attr($choice).'"></td></tr>';echo '<tr><th>Doğru cevap</th><td><select name="correct">';for($i=0;$i<4;$i++)echo '<option value="'.$i.'" '.selected((int)($q['correct']??0),$i,false).'>'.esc_html(chr(65+$i)).'</option>';echo '</select></td></tr><tr><th>Açıklama (isteğe bağlı)</th><td><textarea name="explanation" class="large-text">'.esc_textarea($q['explanation']??'').'</textarea></td></tr><tr><th>Aktif</th><td><label><input type="checkbox" name="active" value="1" '.checked((int)($q['active']??1),1,false).'> Sınav havuzunda</label></td></tr></table>';submit_button('Kaydet');echo '</form><hr><h2>Mevcut Sorular</h2>';
  $page=max(1,absint($_GET['paged']??1));$rows=$wpdb->get_results($wpdb->prepare("SELECT id,level,category,question,active FROM $table ORDER BY FIELD(level,'A1','A2','B1','B2','C1'),id LIMIT 50 OFFSET %d",($page-1)*50),ARRAY_A);$count=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table");echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Seviye</th><th>Kategori</th><th>Soru</th><th>Aktif</th><th>İşlem</th></tr></thead><tbody>';foreach($rows as $r){$url=admin_url('admin.php?page=blc-placement-test&edit='.$r['id']);$del=wp_nonce_url(admin_url('admin-post.php?action=blc_pt_delete_question&id='.$r['id']),'blc_pt_delete_'.$r['id']);echo '<tr><td>'.esc_html($r['id']).'</td><td>'.esc_html($r['level']).'</td><td>'.esc_html($r['category']).'</td><td>'.esc_html(wp_trim_words($r['question'],20)).'</td><td>'.($r['active']?'Evet':'Hayır').'</td><td><a href="'.esc_url($url).'">Düzenle</a> · <a href="'.esc_url($del).'" onclick="return confirm(\'Bu soruyu silmek istiyor musunuz?\')">Sil</a></td></tr>';}echo '</tbody></table>';
  for($i=1;$i<=ceil($count/50);$i++)echo '<a style="margin-right:12px" href="'.esc_url(admin_url('admin.php?page=blc-placement-test&paged='.$i)).'">'.$i.'</a>';echo '</div>';
 }
 static function save_question(){if(!current_user_can('manage_options'))wp_die('Yetki yok');check_admin_referer('blc_pt_save');global $wpdb;$level=sanitize_text_field(self::request_post('level'));$category=sanitize_text_field(self::request_post('category'));$question=sanitize_textarea_field(self::request_post('question'));$raw=isset($_POST['choices'])&&is_array($_POST['choices'])?array_map('sanitize_text_field',wp_unslash($_POST['choices'])):[];$correct=isset($_POST['correct'])?absint($_POST['correct']):5;
  if(!in_array($level,self::levels(),true)||!in_array($category,['grammar','vocabulary','reading'],true)||trim($question)===''||count($raw)!==4||count(array_filter($raw,'strlen'))!==4||$correct>3)wp_die('Eksik veya geçersiz soru verisi.');
  $data=['level'=>$level,'category'=>$category,'question'=>$question,'choices'=>wp_json_encode(array_values($raw)),'correct'=>$correct,'explanation'=>sanitize_textarea_field(self::request_post('explanation')),'active'=>isset($_POST['active'])?1:0];$id=absint($_POST['id']??0);if($id)$wpdb->update(self::name(self::TABLE),$data,['id'=>$id]);else $wpdb->insert(self::name(self::TABLE),$data);wp_safe_redirect(admin_url('admin.php?page=blc-placement-test'));exit;
 }
 static function delete_question(){if(!current_user_can('manage_options'))wp_die('Yetki yok');$id=absint($_GET['id']??0);check_admin_referer('blc_pt_delete_'.$id);global $wpdb;$wpdb->delete(self::name(self::TABLE),['id'=>$id],['%d']);wp_safe_redirect(admin_url('admin.php?page=blc-placement-test'));exit;}
 static function admin_leads(){if(!current_user_can('manage_options'))wp_die('Yetki yok');global $wpdb;$page=max(1,absint($_GET['paged']??1));$table=self::name(self::LEADS);$attempts=self::name(self::ATTEMPTS);$rows=$wpdb->get_results($wpdb->prepare("SELECT l.*,a.result_level,a.scores FROM $table l LEFT JOIN $attempts a ON l.attempt_id=a.id ORDER BY l.id DESC LIMIT 50 OFFSET %d",($page-1)*50),ARRAY_A);echo '<div class="wrap"><h1>İsteğe Bağlı Danışmanlık Başvuruları</h1><p>Yalnızca öğrencinin sonuç sonrası kendi isteğiyle ilettiği bilgiler gösterilir. KVKK aydınlatma metninizi ve saklama politikanızı yapılandırın.</p><table class="widefat striped"><thead><tr><th>Tarih</th><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Şube</th><th>Sonuç</th><th>İşlem</th></tr></thead><tbody>';foreach($rows as $r){$del=wp_nonce_url(admin_url('admin-post.php?action=blc_pt_delete_lead&id='.$r['id']),'blc_pt_delete_lead_'.$r['id']);echo '<tr><td>'.esc_html($r['created_at']).'</td><td>'.esc_html($r['full_name']).'</td><td>'.esc_html($r['email']).'</td><td>'.esc_html($r['phone']).'</td><td>'.esc_html($r['branch']).'</td><td>'.esc_html($r['result_level']).'</td><td><a href="'.esc_url($del).'" onclick="return confirm(\'Başvuruyu kalıcı silmek istiyor musunuz?\')">Sil</a></td></tr>';}echo '</tbody></table>';$count=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table");for($i=1;$i<=ceil($count/50);$i++)echo '<a style="margin-right:12px" href="'.esc_url(admin_url('admin.php?page=blc-pt-leads&paged='.$i)).'">'.$i.'</a>';echo '</div>';}
 static function delete_lead(){if(!current_user_can('manage_options'))wp_die('Yetki yok');$id=absint($_GET['id']??0);check_admin_referer('blc_pt_delete_lead_'.$id);global $wpdb;$wpdb->delete(self::name(self::LEADS),['id'=>$id],['%d']);wp_safe_redirect(admin_url('admin.php?page=blc-pt-leads'));exit;}
}
BLC_Placement_Test::init();
} else {
 // WordPress loads active plugins before loading a second copy for activation.
 // Stop that activation with an actionable message instead of a class fatal.
 register_activation_hook(__FILE__, static function () {
  wp_die('BLC Placement Test başka bir klasörde zaten etkin. Eklentiler ekranında mevcut BLC Placement Test kopyasını devre dışı bırakın; ardından yalnızca 1.1.2 sürümünü etkinleştirin. Soru ve başvuru kayıtları korunur.');
 });
 add_action('admin_notices', static function () {
  echo '<div class="notice notice-error"><p>BLC Placement Test birden fazla klasörde etkin. Eklentiler ekranından kopyaları devre dışı bırakıp yalnızca güncel sürümü etkinleştirin.</p></div>';
 });
}
