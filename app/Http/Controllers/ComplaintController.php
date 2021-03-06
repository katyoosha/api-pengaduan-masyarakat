<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $complaints = Complaint::get();
        $response = [
                'message' => 'Data Laporan',
                'data' => $complaints
        ];
        return response()->json($response, 201);
    }

    public function searchPeopleComplaint(Request $request){

        $fields = json_decode($request->getContent(), true);

        $keyword = $fields["cari"];
        $nik = $fields["nik"];

        $data = DB::table('complaints')->where("complaint_deleted", "not_deleted")->where("nik", $nik)->where('judul_laporan', 'like', "$keyword%")->get();

        $response = [
            "message" => "Berhasil Dapat Data",
            "data" => $data,
            "cari" => $keyword
        ];

        return response()->json($response, 201);
    }

    public function showPeopleComplaints($nik){
        $complaints = DB::table('complaints')->where("nik", $nik)->get();
        $response = [
                'message' => 'Data Laporan',
                'data' => $complaints
        ];
        return response()->json($response, 200);
    }

    public function showNullComplaints()
    {
        $complaints = DB::table('complaints')->where('status', "0")->get();
        $response = [
                'message' => 'Data Laporan Belum di verifikasi',
                'data' => $complaints
        ];
        return response()->json($response, 201);
    }

    public function showComplaints(){
        $complaints = DB::table('complaints')->where('status', '!=', null )->where('status', '!=', "0")->get();
        $response = [
                'message' => 'Data Laporan',
                'data' => $complaints
        ];
        return response()->json($response, 201);
    }

    public function showProcessComplaints(){
        $complaints = DB::table('complaints')->where('status', "proses")->get();
        $response = [
                'message' => 'Data Laporan di proses',
                'data' => $complaints
        ];
        return response()->json($response, 201);
    }

    public function showCompletedComplaints(){
        $complaints = DB::table('complaints')->where('status', "selesai")->get();
        $response = [
                'message' => 'Data Laporan selesai',
                'data' => $complaints
        ];
        return response()->json($response, 201);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'nik' => 'required|max:16',
            'judul_laporan' => 'required',
            'isi_laporan' => 'required',
            'foto' => '',
            'tipe' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'tanggal_kejadian' => ""
        ]);

        $tipe = "";
        if($fields['tipe'] === "PENGADUAN"){
            $tipe = "pengaduan";
        }elseif($fields["tipe"] === "KRITIK & SARAN"){
            $tipe = "kritik_saran";
        }elseif ($fields["tipe"] === "PERTANYAAN") {
            $tipe = "pertanyaan";
        }

        if(!empty($request->file('foto'))){
           $request->file('foto')->store('foto', 'public');

           $complaints = Complaint::create([
                'nik' => $fields['nik'],
                'judul_laporan' => $fields['judul_laporan'],
                'isi_laporan' => $fields['isi_laporan'],
                'foto' => $request->file('foto')->hashName(),
                'tipe' => $tipe,
                'provinsi_kejadian' => $fields['provinsi'],
                'kota_kejadian' => $fields['kota'],
                'kecamatan_kejadian' => $fields['kecamatan'],
                'tanggal_kejadian' => $fields['tanggal_kejadian'],
                'tanggal_laporan' => date("Y-m-d"),
                'status' => "0",
                'complaint_deleted' => "not_deleted"
            ]);
        } else {
             $complaints = Complaint::create([
                'nik' => $fields['nik'],
                'judul_laporan' => $fields['judul_laporan'],
                'isi_laporan' => $fields['isi_laporan'],
                'foto' => null,
                'tipe' => $tipe,
                'provinsi_kejadian' => $fields['provinsi'],
                'kota_kejadian' => $fields['kota'],
                'kecamatan_kejadian' => $fields['kecamatan'],
                'tanggal_kejadian' => $fields['tanggal_kejadian'],
                'tanggal_laporan' => date("Y-m-d"),
                'status' => "0",
                'complaint_deleted' => "not_deleted"
            ]);
        }


        $response = [
            'data' => $complaints,
            'message' => 'Laporan berhasil di create!'
        ];

        return response()->json($response, 201);
    }

    public function verify($id){
        $data = DB::table('complaints')->where('id_pengaduan', $id)->update(['status' => 'proses']);

        $response = [
            'data' => $data,
            'message' => 'Berhasil Verifikasi'
        ];

        return response()->json($response, 201);
    }

    public function reject($id){
        $data = DB::table('complaints')->where('id_pengaduan', $id)->update(['status' => 'tolak']);

        $response = [
            'data' => $data,
            'message' => 'Berhasil Menolak'
        ];

        return response()->json($response, 201);
    }

    public function complete(Request $request, $id){
        $data = DB::table('complaints')->where('id_pengaduan', $id)->update(['status' => 'selesai']);

         $response = [
            'data' => $data,
            'message' => 'Berhasil Selesai'
        ];

        return response()->json($response, 201);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data_pengaduan = DB::table('complaints')->where('id_pengaduan', $id)->get();
        $data_tanggapan = DB::table('responses')
                            ->join('officers', 'responses.id_petugas', '=', 'officers.id_petugas')
                            ->select('responses.*', 'officers.nama_petugas')
                            ->where('id_pengaduan', $id)->where("response_deleted", "not_deleted")->get();

        $response = [
            'data_pengaduan' => $data_pengaduan,
            'data_tanggapan' => $data_tanggapan
        ];

        return response()->json($response, 201);
    }

    public function showPeopleDetailComplaint($id){
        $data_pengaduan = DB::table('complaints')->where('id_pengaduan', $id)->where("complaint_deleted", "not_deleted")->get();
        $data_tanggapan = DB::table('responses')
                            ->join('officers', 'responses.id_petugas', '=', 'officers.id_petugas')
                            ->select('responses.*', 'officers.nama_petugas')
                            ->where('id_pengaduan', $id)->where("response_deleted", "not_deleted")->get();

        $response = [
            'data_pengaduan' => $data_pengaduan,
            'data_tanggapan' => $data_tanggapan
        ];

        return response()->json($response, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fields = $request->validate([
            'judul_laporan' => 'required',
            'isi_laporan' => 'required',
            'tipe' => 'required',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
            'tanggal_kejadian' => ""
        ]);

        $tipe = "";
        if($fields['tipe'] === "PENGADUAN"){
            $tipe = "pengaduan";
        }elseif($fields["tipe"] === "KRITIK & SARAN"){
            $tipe = "kritik_saran";
        }elseif ($fields["tipe"] === "PERTANYAAN") {
            $tipe = "pertanyaan";
        }

             $affected = DB::table('complaints')
              ->where('id_pengaduan', $id)
              ->update([
                'judul_laporan' => $fields['judul_laporan'],
                'isi_laporan' => $fields['isi_laporan'],
                'tipe' => $tipe,
                'provinsi_kejadian' => $fields['provinsi'],
                'kota_kejadian' => $fields['kota'],
                'kecamatan_kejadian' => $fields['kecamatan'],
                'tanggal_kejadian' => $fields['tanggal_kejadian'],
            ]);


        $response = [
            'data' => $affected,
            'message' => 'Laporan berhasil di create!'
        ];

        return response()->json($response, 201);
    }

    public function userDelete(Request $request, $id){
        $data = DB::table('complaints')->where('id_pengaduan', $id)->delete();
        return response()->json($data, 201);
    }

    public function officerDelete(Request $request, $id){
        DB::select('call officerDeleteComplaint(?)', array($id));
    }

    public function showPeopleProcessComplaint($nik) {
        $data = DB::table('complaints')->where("nik", $nik)->where("status", "proses")->get();

        $response = [
            "data" => $data,
            "message" => "Berhasil Dapatkan Data"
        ];

        return response()->json($response, 201);
    }

    public function showPeopleDoneComplaint($nik){
        $data = DB::table('complaints')->where("nik", $nik)->where("status", "selesai")->get();

        $response = [
            "data" => $data,
            "message" => "Berhasil Dapatkan Data"
        ];

        return response()->json($response, 201);
    }

    public function getDataHome(){
        $proses = Complaint::where('status', "proses")->count();
        $selesai = Complaint::where('status', 'selesai')->count();
        $belum_diverifikasi = Complaint::where('status', '0')->count();
        $semua = DB::table('complaints')->count();

        $response = [
            "semua" => $semua,
            "proses" => $proses,
            "selesai" => $selesai,
            "belum_diverifikasi" => $belum_diverifikasi,
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
