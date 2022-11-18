<?php

namespace App\Controllers;

// use App\Models\itemModel;
use App\Libraries\Auth as LibrariesAuth;

class MstItem extends BaseController
{
    public $db_myax;
    public $AGR_TAXBUPELID;
    public $KODE_AGR_TAXBUPELID;
    function __construct()
    {
        date_default_timezone_set("Asia/Jakarta");
        $this->auth = new LibrariesAuth;
        $this->auth->routeAccess();
        $this->session = \Config\Services::session();
        $this->db_myax = \Config\Database::connect('myax');

        // $query = $this->db_myax->query('select top 10 * from inventtable ');
        // $result = $query->getresult();
        // print_r($result);
        // die;
        $this->url = base_url("mstitem");
    }

    public function index()
    {
        $data["title"] = 'Buku Baru';
        $data["folder"] = ['Master', 'Item'];
        $data["content"] = "content/mstitem";
        $data["info"] = $this->session->getFlashdata('info'); //$data;
        return view('index', $data);
    }

    function ajax()
    {
        $data = [];
        $iTotal = 0;
        $iFilteredTotal = 0;
        $tahunini  = date("Y");
        $this->axdb = \Config\Database::connect('myax');

        // 'inventtable.AGR_ITEMSEGMENT',
        // 'inventtable.GROSSWIDTH as panjang',
        // 'inventtable.GROSSHEIGHT as lebar',
        // 'inventtable.NETWEIGHT',
        // 'inventtable.GROSSDEPTH as jml_halaman',

        $aColumns = array(
            'inventtable.ITEMID',
            'inventtable.ERL_ITEMJOBID',
            'inventtable.ITEMNAME',
            'inventtable.AGR_PENGARANG',
            'inventtable.ITEMORIGIN',
            'inventtable.AGR_ITEMGRADE',
            'inventtable.AGR_ITEMLEVEL',
            'inventtable.AGR_ITEMCURRICULUM',
            'inventtable.DIMENSION2_',
            'inventtable.ITEMGROUPID',
            'inventtable.AGR_ITEMSERIESID',
            'inventtable.AGR_PUBLISHDATE',
            'inventtable.AGR_PUBLISHYEAR',
            'ERL_RoyaltyVendor.Accountnum',
            'InventTableModule.MarkupGroupId',
            'InventTableModule.ModuleType',
            'dimensions.description',
            'AGR_GradeItem.AGR_GradeName',
            'AGR_InventBrand.AGR_BrandName'
        );

        $aColumns_ = array(
            'ITEMID',
            'ERL_ITEMJOBID',
            'ITEMNAME',
            'AGR_PENGARANG',
            'ITEMORIGIN',
            'AGR_ITEMGRADE',
            'AGR_ITEMLEVEL',
            'AGR_ITEMCURRICULUM',
            'DIMENSION2_',
            'ITEMGROUPID',
            'AGR_ITEMSERIESID',
            'AGR_PUBLISHDATE',
            'AGR_PUBLISHYEAR',
            'Accountnum',
            'MarkupGroupId',
            'ModuleType',
            'description',
            'AGR_GradeName',
            'AGR_BrandName'
        );


        $sIndexColumn     = "inventtable.ITEMID";
        $sTable            = "inventtable
								INNER JOIN ERL_RoyaltyVendor on ERL_RoyaltyVendor.ITEMID=inventtable.ITEMID AND inventtable.DATAAREAID = ERL_RoyaltyVendor.DATAAREAID 
								INNER JOIN InventTableModule on InventTableModule.ITEMID=inventtable.ITEMID AND InventTableModule.ModuleType = '2' AND inventtable.DATAAREAID =  InventTableModule.DATAAREAID
								INNER JOIN dimensions on dimensions.num = inventtable.DIMENSION2_ AND inventtable.DATAAREAID = dimensions.DATAAREAID
								INNER JOIN AGR_GradeItem on AGR_GradeItem.AGR_ItemGrade = inventtable.AGR_ItemGrade AND inventtable.DATAAREAID = AGR_GradeItem.DATAAREAID 
								INNER JOIN AGR_InventBrand on AGR_InventBrand.AGR_BrandId = inventtable.ITEMORIGIN AND inventtable.DATAAREAID = AGR_InventBrand.DATAAREAID";

        $sOrder         = " ORDER BY inventtable.ITEMID ";
        $sWhere_total     = "";

        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            $sOrder .= "" . $aColumns[intval($_POST['order'][0]['column'])] . " " . ($_POST['order'][0]['dir'] === 'asc' ? 'asc' : 'desc');
        }

        $sWhere_total     = " 
                            WHERE erl_royaltyvendor.PIC = '1'
							AND (inventtable.ItemGroupId='01' 
                            OR inventtable.ItemGroupId='02'
                            OR inventtable.ItemGroupId='03') 
                            AND dimensions.dimensioncode = '1' 
                            AND inventtable.AGR_PUBLISHYEAR = $tahunini
                            AND inventtable.DATAAREAID='erl' 
                            AND erl_royaltyvendor.DATAAREAID='erl' 
                            AND dimensions.DATAAREAID='erl'
                            ";
        $sWhere           =  " 
                             WHERE erl_royaltyvendor.PIC = '1' 
                             AND (inventtable.ItemGroupId='01' 
                             OR inventtable.ItemGroupId='02'
                             OR inventtable.ItemGroupId='03')
                             AND dimensions.dimensioncode = '1' 
                             AND inventtable.AGR_PUBLISHYEAR = $tahunini
                             AND inventtable.DATAAREAID='erl' 
                             AND erl_royaltyvendor.DATAAREAID='erl' 
                             AND dimensions.DATAAREAID='erl'
                             ";

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= $aColumns[$i] . " LIKE '%" . addslashes($_POST['search']['value']) . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['columns'][$i]) && $_POST['columns'][$i]['searchable'] == "true" && $_POST['columns'][$i]['search']['value'] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i] . " LIKE '%" . addslashes($_POST['columns'][$i]['search']['value']) . "%' ";
            }
        }

        $top     = (isset($_POST['start'])) ? ((int) $_POST['start']) : 10;
        $limit   = (isset($_POST['length'])) ? ((int) $_POST['length']) : 10;

        // $sQuery  = "SELECT TOP $limit " . implode(",", $aColumns) . "
        // 				FROM $sTable
        // 				$sWhere " . (($sWhere == "") ? " WHERE " : " AND ") . " $sIndexColumn NOT IN
        // 				(
        // 					SELECT TOP $top $sIndexColumn
        // 					FROM $sTable
        // 					$sOrder
        // 				)
        // 				$sOrder ";

        // $sQuery  = "SELECT  "  . implode(",", $aColumns) . " FROM $sTable $sWhere ORDER BY
        // $sIndexColumn OFFSET $top ROWS FETCH NEXT $limit ROWS ONLY";

        // query sql new


        $sQuery = "select " . implode(",", $aColumns_) .
            " from (
            select top $limit " . implode(",", $aColumns_) .
            " from (
                    select  top ($top + $limit) " . implode(",", $aColumns) .
            " from $sTable $sWhere " . " order by $sIndexColumn asc) tmp1
                order by itemid desc) tmp1 
             order by itemid asc";

        // echo $sQuery;
        // // echo $sWhere;
        // die;

        $rResult        = $this->axdb->query($sQuery);
        #echo $this->axdb->getLastQuery();die;
        // var_dump($rResult);
        // // // echo $sWhere;
        // die;

        /* Data set length after filtering */
        $sQueryCnt      = "SELECT $sIndexColumn FROM $sTable $sWhere ";
        $rResultCnt     = $this->axdb->query($sQueryCnt);
        $iFilteredTotal = $rResultCnt->getNumRows();

        /* Total data set length */
        $sQuery         = "SELECT $sIndexColumn FROM $sTable $sWhere_total";
        $rResultTotal    = $this->axdb->query($sQuery);
        $iTotal         = $rResultTotal->getNumRows();

        $no = $top;

        foreach ($rResult->getResult() as $rows) {

            // $button = '
            // <div class="btn-group">
            // <a class="btn btn-danger btn-xs" onClick="return confirm(' . "'hapus data ini?'" . ');" href="' . $this->url . '/delete/' . trim(base64_encode($rows->id), '=') . '' . '" title="View"><i class="fa fa-trash"></i></a>
            //     <a  class="btn btn-primary btn-xs" href="' . $this->url . '/edit/' . trim(base64_encode($rows->id), '=') . '' . '"><i class="fa fa-edit"></i></a>
            // </div>
            // ';

            // <div class="btn-group">
            // </div>

            // <a href="' . $this->url . '/print/' . trim(base64_encode($rows->ITEMID), '=') . '' . '" class="btn btn-pill btn-outline-success btn-air-success btn-xs">Print</a>
            // </div>
            $button = '
            <a href="' . $this->url . '/edit/' . trim(base64_encode($rows->ITEMID), '=') . '' . '" class="btn btn-pill btn-outline-success btn-air-success btn-xs" target="_blank">Detail</a>
            ';




            // <button class="btn btn-outline-primary" type="button" href="' . $this->url . '/edit/' . trim(base64_encode($rows->ITEMID), '=') . '' . '">Detil</button>
            // <a href="' . $this->url . '/edit/' . trim(base64_encode($rows->ITEMID), '=') . '' . '" class="btn btn-primary">Detail</a>

            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $rows->ITEMID;
            $row[] = $rows->ITEMNAME;
            $row[] = $rows->AGR_PENGARANG;
            $row[] = date('Y-m-d', strtotime($rows->AGR_PUBLISHDATE));
            $row[] = $button;
            // $row[] = "";
            $data[] = $row;
        }

        /* Output */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => $data
        );
        echo json_encode($output);
        $this->axdb->close();
    }

    public function edit()
    {

        if ($this->request->uri->getSegment(4) === FALSE) {
            return redirect();
        } else {
            $itemid = base64_decode($this->request->uri->getSegment(3));

            // tampilkan form create
            // $mst = $this->db_myax->table("inventtable");
            $query = "select 
            aa.ITEMID
            ,bb.EXTCODEVALUE
            ,bb.AGR_TAXBUPELID
            ,'R87' as kodepemasok
            ,'E017' as kodetu
            ,ff.PERCENT1
            ,ff.ITEMRELATION
            ,'Konsinyasi' as sistempembayaran
            ,aa.ITEMNAME
            ,aa.AGR_PENGARANG
            ,bb.AMOUNT
            ,aa.AGR_ITEMSEGMENT
            ,bb.AGR_GRADENAME
            ,ee.designcover
            ,ee.penerjemah
            ,ee.editor
            ,aa.GROSSWIDTH as panjang
            ,aa.GROSSHEIGHT as lebar
            ,aa.NETWEIGHT
            ,aa.GROSSDEPTH as jml_halaman
            ,'2 warna ' as warna_isi
            ,CONVERT(VARCHAR(19),aa.AGR_PUBLISHDATE,106) as tglterbit
            ,dd.EXTERNALITEMID as originid
            ,ee.warna
            ,convert(text,ee.sinopsis) as sinopsis
            from INVENTTABLE as aa
            left join ERL_ITEMISBN as bb on aa.ITEMID=bb.ITEMID and aa.DATAAREAID=bb.DATAAREAID
            left join ERL_ITEMMASTER as cc on aa.ITEMID=cc.ITEMID and aa.DATAAREAID=cc.DATAAREAID
            left join CUSTVENDEXTERNALITEM as dd on aa.ITEMID=dd.ITEMID and aa.DATAAREAID=dd.DATAAREAID
            left join erl_iteminfo as ee on aa.ITEMID=ee.ITEMID and aa.DATAAREAID=ee.DATAAREAID
            left join (
                        select 	bb.ITEMID,aa.PERCENT1,aa.ITEMRELATION
                        from PRICEDISCTABLE as aa
                        join INVENTTABLEMODULE as bb on aa.ITEMRELATION=bb.LINEDISC and aa.DATAAREAID=bb.DATAAREAID
                        where bb.ITEMID='$itemid' 
                            and aa.DATAAREAID='erl'
                            and aa.TODATE='01/01/1900'
                            and aa.ACCOUNTRELATION='Gramedia'
                            and bb.MODULETYPE=2
                        ) as ff on aa.itemid=ff.itemid
            where aa.DATAAREAID='erl'
                and aa.ITEMID='$itemid'
                 ";
            $item = $this->db_myax->query($query)->getRow();

            if (trim($item->ITEMRELATION) == '') {
                $AGR_TAXBUPELID = "DTP";
                $item->KODE_AGR_TAXBUPELID = "152428";
            } else {
                $AGR_TAXBUPELID = "BKP";
                $item->KODE_AGR_TAXBUPELID = "152429";
            }


            // $ada = 0;
            // while (odbc_fetch_row($item)) {
            //     $ada = 1;
            //     $ITEMRELATION        = odbc_result($item, "ITEMRELATION");
            // }

            // print_r($item);
            // die;

            // $mst->select("*");
            // $mst->where("itemid = '$itemid'");
            // $item = $mst->get()->getRow();


            if ($item) {
                $data["title"] = 'Edit Item';
                $data["folder"] = ['Master', 'item', "Edit"];
                $data["content"] = "content/mstitem_edit";
                $data["item"] = $item;
                $data["validation"] = $this->validator;
                $data["itemid"] = $itemid;
                $data["filecover"] = "base_url('public/assets/images/cover/" . $itemid . ".JPG')";

                // if (trim($ITEMRELATION) == '') {
                //     $AGR_TAXBUPELID = "DTP";
                //     $KODE_AGR_TAXBUPELID = "152428";
                // } else {
                //     $AGR_TAXBUPELID = "BKP";
                //     $KODE_AGR_TAXBUPELID = "152429";
                // }

                // "<?= base_url('public/assets/images/cover/" . $itemid . ".jpg";

                // print_r('itemid: ' . $date["filecover"]);
                // die;

                return view('content/mstitem_edit', $data);
            }
            $this->session->setFlashdata('info', "data not found");
            return redirect()->to("mstitem");
        }
    }

    public function save_edit()
    {
        helper(['form', 'url']);

        // lakukan validasi
        $rules = [
            'itemname' => 'required',
        ];

        $itemid = base64_decode($this->request->uri->getSegment(3));
        $isDataValid = false;

        if (isset($_POST["cancel"])) {
            $this->session->setFlashdata('info', "edit batal");
            return redirect()->to("mstitem");
        }

        if (isset($_POST["submit"])) {
            $isDataValid = $this->validate($rules);
        }
        $id =  $this->request->getPost('fid');
        if ($isDataValid) {
            // $photo = $this->request->getFile('photo');
            // $photoname = $this->request->getPost('oldPhotoName');
            // if ($photo->getName() != '') {
            //     $path_to_file = '../uploads/bidan/' . $photoname;
            //     if (file_exists($path_to_file)) {
            //         unlink($path_to_file);
            //     }
            //     $photoname = $photo->getRandomName();
            //     $photo->move('uploads/bidan/', $photoname);
            // }
            $data = [
                "itemname" => $this->request->getPost('itemname'),
            ];

            $mst = $this->db->table("inventtable");
            $mst->where('itemid', $itemid);
            $mst->update($data);
            $this->session->setFlashdata('info', "data berhasil disimpan");
            return redirect()->to("mstitem");
        } else {

            $mst = $this->db->table("inventtable");
            $mst->select("*");
            $mst->where("itemid = '$itemid'");
            $item = $mst->get()->getRow();
            if ($item) {
                $data["title"] = 'Edit Item';
                $data["folder"] = ['Master', 'Item', "Edit"];
                $data["content"] = "content/mstitem_edit";
                $data["item"] = $item;
                $data["validation"] = $this->validator;
                $data["itemd"] = $itemid;

                return view('index', $data);
            }
        }

        $this->session->setFlashdata('info', "data not found");
        return redirect()->to("mstitem");
    }


    public function save_cover()
    {

        $ok = 0;
        $itemid = $_POST['itemid'];

        echo $itemid;
        die;
        if (trim($itemid) == "") {
            echo "Gagal!";
            return;
        }
    }
    public function update_spek()
    {

        // session_start();
        // error_reporting(0);
        // $s = "https://" . $_SERVER['HTTP_HOST'];
        // if (!isset($_SESSION['usernamespk'])) {
        //     if (file_exists('$s/spk/index.php')) {
        //         header("Location:$s/spk/index.php");
        //     } else {
        //         header("Location:$s/spk/index.php");
        //     }
        // }

        $no = $_GET['no'];
        $text = $_GET['text'];
        $itemid = $_GET['itemid'];

        // echo $itemid;
        if ($no == 1) {
            $fields = " designcover='$text' ";
            $field = "designcover";
        }

        if ($no == 2) {
            $fields = " penerjemah='$text' ";
            $field = "penerjemah";
        }

        if ($no == 3) {
            $fields = " editor='$text' ";
            $field = "editor";
        }

        if ($no == 4) {
            $fields = " warna='$text' ";
            $field = "warna";
        }


        // include '../conn_odbc.php';
        $this->axdb = \Config\Database::connect('myax');


        $query = "select * from erl_iteminfo where DATAAREAID='erl' and ITEMID='$itemid'";
        // $result = odbc_exec($connectAX, $query);
        $result = $this->axdb->query($query);
        $ada = 0;
        if ($result->getNumRows() > 0) {
            $ada = 1;
        }
        // while (odbc_fetch_row($result)) {
        //     $ada = 1;
        // }

        if ($ada == 1) {
            $query = "
			update erl_iteminfo set
			$fields
			where DATAAREAID='erl' and ITEMID='$itemid'
		 ";
        } else {
            $query = "insert into erl_iteminfo (itemid,$field,dataareaid,recid) values ('$itemid','$text','erl',1)";
        }




        // $result = odbc_exec($connectAX, $query);
        $result = $this->axdb->query($query);
        if ($result) {
            echo "$text";
        } else {
            echo "<font color='red'>Gagal Simpan </font>";
        }
        // odbc_close($connectAX);
    }
}
