<?php
function is_admin() {
    if (get_user()['role'] == 1) return true;
    return false;
}

function is_moderator() {
    return false;
}

function admin_get_user_lists() {
    $sql = "SELECT * FROM members ";
    $sql .= " ORDER BY id DESC ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function admin_get_post_lists() {
    $sql = "SELECT * FROM posts ";
    $sql .= " ORDER BY id DESC ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function admin_update_user($val, $id) {
    foreach($val as $key => $value) {
        db()->query("UPDATE members SET $key=?  WHERE id=?" , $value, $id);
    }
    return true;
}

function save_admin_settings($val) {
    foreach($val as $key => $value) {
        $query = db()->query("SELECT id FROM settings WHERE setting_key=? ", $key);
        if ($query->rowCount() > 0) {
            db()->query("UPDATE settings SET setting_value=? WHERE setting_key=? ", $value, $key);
        } else {
            db()->query("INSERT INTO settings (setting_key,setting_value) VALUES(?,?)", $key, $value);
        }
    }

    load_admin_settings(); //to silently load admin settings
    return true;
}

function load_admin_settings() {
    $query = db()->query("SELECT setting_key,setting_value FROM settings ");
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        app()->setConfig($fetch['setting_key'], $fetch['setting_value']);
    }
}

function add_tag($name, $isAdmin) {
    $isAdmin = ($isAdmin) ? 1 : 0;
    db()->query("INSERT INTO tags (name,from_admin)VALUES(?,?)", $name, $isAdmin);
    return db()->lastInsertId();
}

function delete_tag($id) {
    db()->query("DELETE FROM tags WHERE id=?", $id);
}

function get_tags(){
    $query = db()->query("SELECT * FROM tags WHERE from_admin=? ", 1);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function find_tags($tag) {
    $query = db()->query("SELECT * FROM tags WHERE name LIKE ? ", '%'.$tag.'%');
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_tag_id($tag) {
    $query = db()->query("SELECT id FROM tags WHERE name=? ", $tag);
    if ($query->rowCount() > 0) {
        $fetch = $query->fetch(PDO::FETCH_ASSOC);
        return $fetch['id'];
    } else {
        return add_tag($tag, false);
    }
}

function get_theme_options() {
    $theme = get_theme();
    $result = array();
    $file = path('styles/'.$theme.'/options.php');
    if (file_exists($file)) $result = include_once($file);
    return $result;
}

function get_theme_list() {
    $path = path('styles');
    $handle = opendir($path);
    $result = array();
    while($read = readdir($handle)) {
        if (substr($read, 0,1) != '.') {
            $result[] = $read;
        }
    }

    return $result;
}

function all_languages() {
    return array (
        'ab' => 'Abkhazian',
        'ace' => 'Achinese',
        'ach' => 'Acoli',
        'ada' => 'Adangme',
        'ady' => 'Adyghe',
        'aa' => 'Afar',
        'afh' => 'Afrihili',
        'af' => 'Afrikaans',
        'agq' => 'Aghem',
        'ain' => 'Ainu',
        'ak' => 'Akan',
        'akk' => 'Akkadian',
        'bss' => 'Akoose',
        'akz' => 'Alabama',
        'sq' => 'Albanian',
        'ale' => 'Aleut',
        'arq' => 'Algerian Arabic',
        'en' => 'English',
        'ase' => 'American Sign Language',
        'am' => 'Amharic',
        'egy' => 'Ancient Egyptian',
        'grc' => 'Ancient Greek',
        'anp' => 'Angika',
        'njo' => 'Ao Naga',
        'ar' => 'Arabic',
        'an' => 'Aragonese',
        'arc' => 'Aramaic',
        'aro' => 'Araona',
        'arp' => 'Arapaho',
        'arw' => 'Arawak',
        'hy' => 'Armenian',
        'rup' => 'Aromanian',
        'frp' => 'Arpitan',
        'as' => 'Assamese',
        'ast' => 'Asturian',
        'asa' => 'Asu',
        'cch' => 'Atsam',
        'en_AU' => 'Australian English',
        'de_AT' => 'Austrian German',
        'av' => 'Avaric',
        'ae' => 'Avestan',
        'awa' => 'Awadhi',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'bfq' => 'Badaga',
        'ksf' => 'Bafia',
        'bfd' => 'Bafut',
        'bqi' => 'Bakhtiari',
        'ban' => 'Balinese',
        'bal' => 'Baluchi',
        'bm' => 'Bambara',
        'bax' => 'Bamun',
        'bjn' => 'Banjar',
        'bas' => 'Basaa',
        'ba' => 'Bashkir',
        'eu' => 'Basque',
        'bbc' => 'Batak Toba',
        'bar' => 'Bavarian',
        'bej' => 'Beja',
        'be' => 'Belarusian',
        'bem' => 'Bemba',
        'bez' => 'Bena',
        'bn' => 'Bengali',
        'bew' => 'Betawi',
        'bho' => 'Bhojpuri',
        'bik' => 'Bikol',
        'bin' => 'Bini',
        'bpy' => 'Bishnupriya',
        'bi' => 'Bislama',
        'byn' => 'Blin',
        'zbl' => 'Blissymbols',
        'brx' => 'Bodo',
        'bs' => 'Bosnian',
        'brh' => 'Brahui',
        'bra' => 'Braj',
        'pt_BR' => 'Brazilian Portuguese',
        'br' => 'Breton',
        'en_GB' => 'British English',
        'bug' => 'Buginese',
        'bg' => 'Bulgarian',
        'bum' => 'Bulu',
        'bua' => 'Buriat',
        'my' => 'Burmese',
        'cad' => 'Caddo',
        'frc' => 'Cajun French',
        'en_CA' => 'Canadian English',
        'fr_CA' => 'Canadian French',
        'yue' => 'Cantonese',
        'cps' => 'Capiznon',
        'car' => 'Carib',
        'ca' => 'Catalan',
        'cay' => 'Cayuga',
        'ceb' => 'Cebuano',
        'tzm' => 'Central Atlas Tamazight',
        'dtp' => 'Central Dusun',
        'esu' => 'Central Yupik',
        'shu' => 'Chadian Arabic',
        'chg' => 'Chagatai',
        'ch' => 'Chamorro',
        'ce' => 'Chechen',
        'chr' => 'Cherokee',
        'chy' => 'Cheyenne',
        'chb' => 'Chibcha',
        'cgg' => 'Chiga',
        'qug' => 'Chimborazo Highland Quichua',
        'zh' => 'Chinese',
        'chn' => 'Chinook Jargon',
        'chp' => 'Chipewyan',
        'cho' => 'Choctaw',
        'cu' => 'Church Slavic',
        'chk' => 'Chuukese',
        'cv' => 'Chuvash',
        'nwc' => 'Classical Newari',
        'syc' => 'Classical Syriac',
        'ksh' => 'Colognian',
        'swb' => 'Comorian',
        'swc' => 'Congo Swahili',
        'cop' => 'Coptic',
        'kw' => 'Cornish',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'mus' => 'Creek',
        'crh' => 'Crimean Turkish',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'dak' => 'Dakota',
        'da' => 'Danish',
        'dar' => 'Dargwa',
        'dzg' => 'Dazaga',
        'del' => 'Delaware',
        'din' => 'Dinka',
        'dv' => 'Divehi',
        'doi' => 'Dogri',
        'dgr' => 'Dogrib',
        'dua' => 'Duala',
        'nl' => 'Dutch',
        'dyu' => 'Dyula',
        'dz' => 'Dzongkha',
        'frs' => 'Eastern Frisian',
        'efi' => 'Efik',
        'arz' => 'Egyptian Arabic',
        'eka' => 'Ekajuk',
        'elx' => 'Elamite',
        'ebu' => 'Embu',
        'egl' => 'Emilian',
        'en' => 'English',
        'myv' => 'Erzya',
        'eo' => 'Esperanto',
        'et' => 'Estonian',
        'pt_PT' => 'European Portuguese',
        'es_ES' => 'European Spanish',
        'ee' => 'Ewe',
        'ewo' => 'Ewondo',
        'ext' => 'Extremaduran',
        'fan' => 'Fang',
        'fat' => 'Fanti',
        'fo' => 'Faroese',
        'hif' => 'Fiji Hindi',
        'fj' => 'Fijian',
        'fil' => 'Filipino',
        'fi' => 'Finnish',
        'nl_BE' => 'Flemish',
        'fon' => 'Fon',
        'gur' => 'Frafra',
        'fr' => 'French',
        'fur' => 'Friulian',
        'ff' => 'Fulah',
        'gaa' => 'Ga',
        'gag' => 'Gagauz',
        'gl' => 'Galician',
        'gan' => 'Gan Chinese',
        'lg' => 'Ganda',
        'gay' => 'Gayo',
        'gba' => 'Gbaya',
        'gez' => 'Geez',
        'ka' => 'Georgian',
        'de' => 'German',
        'aln' => 'Gheg Albanian',
        'bbj' => 'Ghomala',
        'glk' => 'Gilaki',
        'gil' => 'Gilbertese',
        'gom' => 'Goan Konkani',
        'gon' => 'Gondi',
        'gor' => 'Gorontalo',
        'got' => 'Gothic',
        'grb' => 'Grebo',
        'el' => 'Greek',
        'gn' => 'Guarani',
        'gu' => 'Gujarati',
        'guz' => 'Gusii',
        'gwi' => 'Gwichʼin',
        'hai' => 'Haida',
        'ht' => 'Haitian',
        'hak' => 'Hakka Chinese',
        'ha' => 'Hausa',
        'haw' => 'Hawaiian',
        'he' => 'Hebrew',
        'hz' => 'Herero',
        'hil' => 'Hiligaynon',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hit' => 'Hittite',
        'hmn' => 'Hmong',
        'hu' => 'Hungarian',
        'hup' => 'Hupa',
        'iba' => 'Iban',
        'ibb' => 'Ibibio',
        'is' => 'Icelandic',
        'io' => 'Ido',
        'ig' => 'Igbo',
        'ilo' => 'Iloko',
        'smn' => 'Inari Sami',
        'id' => 'Indonesian',
        'izh' => 'Ingrian',
        'inh' => 'Ingush',
        'ia' => 'Interlingua',
        'ie' => 'Interlingue',
        'iu' => 'Inuktitut',
        'ik' => 'Inupiaq',
        'ga' => 'Irish',
        'it' => 'Italian',
        'jam' => 'Jamaican Creole English',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'kaj' => 'Jju',
        'dyo' => 'Jola-Fonyi',
        'jrb' => 'Judeo-Arabic',
        'jpr' => 'Judeo-Persian',
        'jut' => 'Jutish',
        'kbd' => 'Kabardian',
        'kea' => 'Kabuverdianu',
        'kab' => 'Kabyle',
        'kac' => 'Kachin',
        'kgp' => 'Kaingang',
        'kkj' => 'Kako',
        'kl' => 'Kalaallisut',
        'kln' => 'Kalenjin',
        'xal' => 'Kalmyk',
        'kam' => 'Kamba',
        'kbl' => 'Kanembu',
        'kn' => 'Kannada',
        'kr' => 'Kanuri',
        'kaa' => 'Kara-Kalpak',
        'krc' => 'Karachay-Balkar',
        'krl' => 'Karelian',
        'ks' => 'Kashmiri',
        'csb' => 'Kashubian',
        'kaw' => 'Kawi',
        'kk' => 'Kazakh',
        'ken' => 'Kenyang',
        'kha' => 'Khasi',
        'km' => 'Khmer',
        'kho' => 'Khotanese',
        'khw' => 'Khowar',
        'ki' => 'Kikuyu',
        'kmb' => 'Kimbundu',
        'krj' => 'Kinaray-a',
        'rw' => 'Kinyarwanda',
        'kiu' => 'Kirmanjki',
        'tlh' => 'Klingon',
        'bkm' => 'Kom',
        'kv' => 'Komi',
        'koi' => 'Komi-Permyak',
        'kg' => 'Kongo',
        'kok' => 'Konkani',
        'ko' => 'Korean',
        'kfo' => 'Koro',
        'kos' => 'Kosraean',
        'avk' => 'Kotava',
        'khq' => 'Koyra Chiini',
        'ses' => 'Koyraboro Senni',
        'kpe' => 'Kpelle',
        'kri' => 'Krio',
        'kj' => 'Kuanyama',
        'kum' => 'Kumyk',
        'ku' => 'Kurdish',
        'kru' => 'Kurukh',
        'kut' => 'Kutenai',
        'nmg' => 'Kwasio',
        'ky' => 'Kyrgyz',
        'quc' => 'Kʼicheʼ',
        'lad' => 'Ladino',
        'lah' => 'Lahnda',
        'lkt' => 'Lakota',
        'lam' => 'Lamba',
        'lag' => 'Langi',
        'lo' => 'Lao',
        'ltg' => 'Latgalian',
        'la' => 'Latin',
        'es_419' => 'Latin American Spanish',
        'lv' => 'Latvian',
        'lzz' => 'Laz',
        'lez' => 'Lezghian',
        'lij' => 'Ligurian',
        'li' => 'Limburgish',
        'ln' => 'Lingala',
        'lfn' => 'Lingua Franca Nova',
        'lzh' => 'Literary Chinese',
        'lt' => 'Lithuanian',
        'liv' => 'Livonian',
        'jbo' => 'Lojban',
        'lmo' => 'Lombard',
        'nds' => 'Low German',
        'sli' => 'Lower Silesian',
        'dsb' => 'Lower Sorbian',
        'loz' => 'Lozi',
        'lu' => 'Luba-Katanga',
        'lua' => 'Luba-Lulua',
        'lui' => 'Luiseno',
        'smj' => 'Lule Sami',
        'lun' => 'Lunda',
        'luo' => 'Luo',
        'lb' => 'Luxembourgish',
        'luy' => 'Luyia',
        'mde' => 'Maba',
        'mk' => 'Macedonian',
        'jmc' => 'Machame',
        'mad' => 'Madurese',
        'maf' => 'Mafa',
        'mag' => 'Magahi',
        'vmf' => 'Main-Franconian',
        'mai' => 'Maithili',
        'mak' => 'Makasar',
        'mgh' => 'Makhuwa-Meetto',
        'kde' => 'Makonde',
        'mg' => 'Malagasy',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mt' => 'Maltese',
        'mnc' => 'Manchu',
        'mdr' => 'Mandar',
        'man' => 'Mandingo',
        'mni' => 'Manipuri',
        'gv' => 'Manx',
        'mi' => 'Maori',
        'arn' => 'Mapuche',
        'mr' => 'Marathi',
        'chm' => 'Mari',
        'mh' => 'Marshallese',
        'mwr' => 'Marwari',
        'mas' => 'Masai',
        'mzn' => 'Mazanderani',
        'byv' => 'Medumba',
        'men' => 'Mende',
        'mwv' => 'Mentawai',
        'mer' => 'Meru',
        'mgo' => 'Metaʼ',
        'es_MX' => 'Mexican Spanish',
        'mic' => 'Micmac',
        'dum' => 'Middle Dutch',
        'enm' => 'Middle English',
        'frm' => 'Middle French',
        'gmh' => 'Middle High German',
        'mga' => 'Middle Irish',
        'nan' => 'Min Nan Chinese',
        'min' => 'Minangkabau',
        'xmf' => 'Mingrelian',
        'mwl' => 'Mirandese',
        'lus' => 'Mizo',
        'ar_001' => 'Modern Standard Arabic',
        'moh' => 'Mohawk',
        'mdf' => 'Moksha',
        'ro_MD' => 'Moldavian',
        'lol' => 'Mongo',
        'mn' => 'Mongolian',
        'mfe' => 'Morisyen',
        'ary' => 'Moroccan Arabic',
        'mos' => 'Mossi',
        'mul' => 'Multiple Languages',
        'mua' => 'Mundang',
        'ttt' => 'Muslim Tat',
        'mye' => 'Myene',
        'naq' => 'Nama',
        'na' => 'Nauru',
        'nv' => 'Navajo',
        'ng' => 'Ndonga',
        'nap' => 'Neapolitan',
        'ne' => 'Nepali',
        'new' => 'Newari',
        'sba' => 'Ngambay',
        'nnh' => 'Ngiemboon',
        'jgo' => 'Ngomba',
        'yrl' => 'Nheengatu',
        'nia' => 'Nias',
        'niu' => 'Niuean',
        'zxx' => 'No linguistic content',
        'nog' => 'Nogai',
        'nd' => 'North Ndebele',
        'frr' => 'Northern Frisian',
        'se' => 'Northern Sami',
        'nso' => 'Northern Sotho',
        'no' => 'Norwegian',
        'nb' => 'Norwegian Bokmål',
        'nn' => 'Norwegian Nynorsk',
        'nov' => 'Novial',
        'nus' => 'Nuer',
        'nym' => 'Nyamwezi',
        'ny' => 'Nyanja',
        'nyn' => 'Nyankole',
        'tog' => 'Nyasa Tonga',
        'nyo' => 'Nyoro',
        'nzi' => 'Nzima',
        'nqo' => 'NʼKo',
        'oc' => 'Occitan',
        'oj' => 'Ojibwa',
        'ang' => 'Old English',
        'fro' => 'Old French',
        'goh' => 'Old High German',
        'sga' => 'Old Irish',
        'non' => 'Old Norse',
        'peo' => 'Old Persian',
        'pro' => 'Old Provençal',
        'or' => 'Oriya',
        'om' => 'Oromo',
        'osa' => 'Osage',
        'os' => 'Ossetic',
        'ota' => 'Ottoman Turkish',
        'pal' => 'Pahlavi',
        'pfl' => 'Palatine German',
        'pau' => 'Palauan',
        'pi' => 'Pali',
        'pam' => 'Pampanga',
        'pag' => 'Pangasinan',
        'pap' => 'Papiamento',
        'ps' => 'Pashto',
        'pdc' => 'Pennsylvania German',
        'fa' => 'Persian',
        'phn' => 'Phoenician',
        'pcd' => 'Picard',
        'pms' => 'Piedmontese',
        'pdt' => 'Plautdietsch',
        'pon' => 'Pohnpeian',
        'pl' => 'Polish',
        'pnt' => 'Pontic',
        'pt' => 'Portuguese',
        'prg' => 'Prussian',
        'pa' => 'Punjabi',
        'qu' => 'Quechua',
        'raj' => 'Rajasthani',
        'rap' => 'Rapanui',
        'rar' => 'Rarotongan',
        'rif' => 'Riffian',
        'rgn' => 'Romagnol',
        'ro' => 'Romanian',
        'rm' => 'Romansh',
        'rom' => 'Romany',
        'rof' => 'Rombo',
        'root' => 'Root',
        'rtm' => 'Rotuman',
        'rug' => 'Roviana',
        'rn' => 'Rundi',
        'ru' => 'Russian',
        'rue' => 'Rusyn',
        'rwk' => 'Rwa',
        'ssy' => 'Saho',
        'sah' => 'Sakha',
        'sam' => 'Samaritan Aramaic',
        'saq' => 'Samburu',
        'sm' => 'Samoan',
        'sgs' => 'Samogitian',
        'sad' => 'Sandawe',
        'sg' => 'Sango',
        'sbp' => 'Sangu',
        'sa' => 'Sanskrit',
        'sat' => 'Santali',
        'sc' => 'Sardinian',
        'sas' => 'Sasak',
        'sdc' => 'Sassarese Sardinian',
        'stq' => 'Saterland Frisian',
        'saz' => 'Saurashtra',
        'sco' => 'Scots',
        'gd' => 'Scottish Gaelic',
        'sly' => 'Selayar',
        'sel' => 'Selkup',
        'seh' => 'Sena',
        'see' => 'Seneca',
        'sr' => 'Serbian',
        'sh' => 'Serbo-Croatian',
        'srr' => 'Serer',
        'sei' => 'Seri',
        'ksb' => 'Shambala',
        'shn' => 'Shan',
        'sn' => 'Shona',
        'ii' => 'Sichuan Yi',
        'scn' => 'Sicilian',
        'sid' => 'Sidamo',
        'bla' => 'Siksika',
        'szl' => 'Silesian',
        'zh_Hans' => 'Simplified Chinese',
        'sd' => 'Sindhi',
        'si' => 'Sinhala',
        'sms' => 'Skolt Sami',
        'den' => 'Slave',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'xog' => 'Soga',
        'sog' => 'Sogdien',
        'so' => 'Somali',
        'snk' => 'Soninke',
        'ckb' => 'Sorani Kurdish',
        'azb' => 'South Azerbaijani',
        'nr' => 'South Ndebele',
        'alt' => 'Southern Altai',
        'sma' => 'Southern Sami',
        'st' => 'Southern Sotho',
        'es' => 'Spanish',
        'srn' => 'Sranan Tongo',
        'zgh' => 'Standard Moroccan Tamazight',
        'suk' => 'Sukuma',
        'sux' => 'Sumerian',
        'su' => 'Sundanese',
        'sus' => 'Susu',
        'sw' => 'Swahili',
        'ss' => 'Swati',
        'sv' => 'Swedish',
        'fr_CH' => 'Swiss French',
        'gsw' => 'Swiss German',
        'de_CH' => 'Swiss High German',
        'syr' => 'Syriac',
        'shi' => 'Tachelhit',
        'tl' => 'Tagalog',
        'ty' => 'Tahitian',
        'dav' => 'Taita',
        'tg' => 'Tajik',
        'tly' => 'Talysh',
        'tmh' => 'Tamashek',
        'ta' => 'Tamil',
        'trv' => 'Taroko',
        'twq' => 'Tasawaq',
        'tt' => 'Tatar',
        'te' => 'Telugu',
        'ter' => 'Tereno',
        'teo' => 'Teso',
        'tet' => 'Tetum',
        'th' => 'Thai',
        'bo' => 'Tibetan',
        'tig' => 'Tigre',
        'ti' => 'Tigrinya',
        'tem' => 'Timne',
        'tiv' => 'Tiv',
        'tli' => 'Tlingit',
        'tpi' => 'Tok Pisin',
        'tkl' => 'Tokelau',
        'to' => 'Tongan',
        'fit' => 'Tornedalen Finnish',
        'zh_Hant' => 'Traditional Chinese',
        'tkr' => 'Tsakhur',
        'tsd' => 'Tsakonian',
        'tsi' => 'Tsimshian',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tcy' => 'Tulu',
        'tum' => 'Tumbuka',
        'aeb' => 'Tunisian Arabic',
        'tr' => 'Turkish',
        'tk' => 'Turkmen',
        'tru' => 'Turoyo',
        'tvl' => 'Tuvalu',
        'tyv' => 'Tuvinian',
        'tw' => 'Twi',
        'kcg' => 'Tyap',
        'udm' => 'Udmurt',
        'uga' => 'Ugaritic',
        'uk' => 'Ukrainian',
        'umb' => 'Umbundu',
        'und' => 'Unknown Language',
        'hsb' => 'Upper Sorbian',
        'ur' => 'Urdu',
        'ug' => 'Uyghur',
        'uz' => 'Uzbek',
        'vai' => 'Vai',
        've' => 'Venda',
        'vec' => 'Venetian',
        'vep' => 'Veps',
        'vi' => 'Vietnamese',
        'vo' => 'Volapük',
        'vro' => 'Võro',
        'vot' => 'Votic',
        'vun' => 'Vunjo',
        'wa' => 'Walloon',
        'wae' => 'Walser',
        'war' => 'Waray',
        'was' => 'Washo',
        'guc' => 'Wayuu',
        'cy' => 'Welsh',
        'vls' => 'West Flemish',
        'fy' => 'Western Frisian',
        'mrj' => 'Western Mari',
        'wal' => 'Wolaytta',
        'wo' => 'Wolof',
        'wuu' => 'Wu Chinese',
        'xh' => 'Xhosa',
        'hsn' => 'Xiang Chinese',
        'yav' => 'Yangben',
        'yao' => 'Yao',
        'yap' => 'Yapese',
        'ybb' => 'Yemba',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'zap' => 'Zapotec',
        'dje' => 'Zarma',
        'zza' => 'Zaza',
        'zea' => 'Zeelandic',
        'zen' => 'Zenaga',
        'za' => 'Zhuang',
        'gbz' => 'Zoroastrian Dari',
        'zu' => 'Zulu',
        'zun' => 'Zuni',
    );
}


function get_languages_list() {
    $path = path('languages');
    $all = all_languages();
    $result = array();
    $open = opendir($path);
    while($read = readdir($open)) {
        if (substr($read, 0, 1) != '.') {
            $lang = str_replace('.php','', $read);
            if (isset($all[$lang])) $result[$lang] = $all[$lang];
        }
    }

    return $result;
}

function get_current_lang_name() {
    $lang = Translation::getInstance()->lang();
    $all = all_languages();
    return $all[$lang];
}

function admin_get_badge_lists() {
    $query = db()->query("SELECT * FROM badges ORDER BY id");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}
function admin_count_user_with_badge($badgeId) {
    $query = db()->query("SELECT id FROM user_badges WHERE badge_id=?", $badgeId);
    return $query->rowCount();
}

function get_badge($id) {
    $query = db()->query("SELECT * FROM badges WHERE id=?", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function delete_badge($id){
    db()->query("DELETE FROM badges WHERE id=?", $id);
    db()->query("DELETE FROM user_badges WHERE badge_id=?", $id);
    return true;
}
function save_badge($val, $badge = null) {
    $name = $val['name'];
    $point = $val['point'];
    $image = ($badge) ? $badge['image'] : '';
    if ($file = input_file('image')) {
        $uploader = new Uploader($file);
        $uploader->setPath('badges/');
        if ($uploader->passed()) {
            $image = $uploader->uploadFile()->result();
        }
    }
    if (!$badge) {
        db()->query("INSERT INTO badges (name,point,image)VALUES(?,?,?)", $name,$point,$image);
    } else {
        db()->query("UPDATE badges SET name=?,point=?,image=? WHERE id=?", $name,$point,$image,$badge['id']);
    }
    return true;
}

function get_user_badges($id, $new =false) {
    if (!config('enable-badge', true)) return array();
    $sql = "SELECT badge_id FROM user_badges WHERE user_id=? ";
    if ($new) {
        $sql .= " AND notify='0' ";
    }
    $query = db()->query($sql, $id);
    $ids = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $fetch['badge_id'];
    }
    if ($ids) {
        $ids = implode(',', $ids);
        $sql = "SELECT * FROM badges WHERE id IN ($ids) ";

        $query = db()->query($sql);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    return array();
}
function mark_badge_seen($id) {
    db()->query("UPDATE user_badges SET notify=? WHERE user_id=? AND badge_id=?", 1, get_userid(), $id);
}
function process_point($key) {
    $point = config($key, 10);
    $newPoint = get_user()['point'] + $point;
    db()->query("UPDATE members SET point =? WHERE id=?", $newPoint,get_userid());
    $query = db()->query("SELECT badge_id FROM user_badges WHERE user_id=? ", get_userid());
    $ids = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $fetch['badge_id'];
    }
    $sql = "SELECT id FROM badges WHERE point <= $newPoint ";
    if ($ids) {
        $ids = implode(',', $ids);
        $sql .= " AND id NOT IN ($ids) ";
    }
    $query = db()->query($sql);
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        db()->query("INSERT INTO user_badges (badge_id,user_id)VALUES(?,?)", $fetch['id'], get_userid());
    }
    return true;
}