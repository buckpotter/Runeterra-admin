<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\BusCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminTripsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth')->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);
    }

    public function index(Request $request)
    {
        $search_text = $request['search'] ?? "";
        $trips = NULL;

        if ($search_text == "") {
            $trips = Trip::sortable()
                ->join('buses', 'trips.IdXe', '=', 'buses.IdXe')
                ->join('bus_routes', 'trips.IdTuyen', '=', 'bus_routes.IdTuyen')
                ->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')
                ->select('trips.*', 'buses.So_xe', 'bus_routes.TenTuyen', 'bus_companies.Ten_NX')
                ->paginate(15);
        } else {
            $trips = Trip::sortable()
                ->join('buses', 'trips.IdXe', '=', 'buses.IdXe')
                ->join('bus_routes', 'trips.IdTuyen', '=', 'bus_routes.IdTuyen')
                ->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')
                ->select('trips.*', 'buses.So_xe', 'bus_routes.TenTuyen', 'bus_companies.Ten_NX')
                ->where('IdChuyen', 'like', "%$search_text%")
                ->orWhere('NgayDi', 'like', "%$search_text%")
                ->orWhere('GioDi', 'like', "%$search_text%")
                ->orWhere('GioDen', 'like', "%$search_text%")
                ->orWhere('So_xe', 'like', "%$search_text%")
                ->orWhere('TenTuyen', 'like', "%$search_text%")
                ->orWhere('Ten_NX', 'like', "%$search_text%")
                ->orWhere('GiaVe', 'like', "%$search_text%")
                ->paginate(15);
        }

        $trips = $trips->appends([
            'search' => $search_text,
            'sort' => $request['sort'] ?? 'IdChuyen',
            'direction' => $request['direction'] ?? 'asc'
        ]);

        return view(
            'trips.index',
            [
                'trips' => $trips,
                'search' => $search_text
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $cities = [
            'H??a B??nh', 'S??n La', '??i???n Bi??n', 'Lai Ch??u', 'L??o Cai', 'Y??n B??i', 'Ph?? Th???', 'H?? Giang', 'Tuy??n Quang', 'Cao B???ng', 'B???c K???n', 'Th??i Nguy??n', 'L???ng S??n', 'B???c Giang', 'Qu???ng Ninh', 'H?? N???i', 'B???c Ninh', 'H?? Nam', 'H???i D????ng', 'H???i Ph??ng', 'H??ng Y??n', 'Nam ?????nh', 'Th??i B??nh', 'V??nh Ph??c', 'Ninh B??nh', 'Thanh H??a', 'Ngh??? An', 'H?? T??nh', 'Qu???ng B??nh', 'Qu???ng Tr???', 'Hu???', '???? N???ng', 'Qu???ng Nam', 'Qu???ng Ng??i', 'B??nh ?????nh', 'Ph?? Y??n', 'Kh??nh H??a', 'Ninh Thu???n', 'B??nh Thu???n', 'TP. H??? Ch?? Minh', 'V??ng T??u', 'B??nh D????ng', 'B??nh Ph?????c', '?????ng Nai', 'T??y Ninh', 'An Giang', 'B???c Li??u', 'B???n Tre', 'C?? Mau', 'C???n Th??', '?????ng Th??p', 'H???u Giang', 'Ki??n Giang', 'Long An', 'S??c Tr??ng', 'Ti???n Giang', 'Tr?? Vinh', 'V??nh Long', 'Kon Tum', 'Gia Lai', '?????k L???k', '?????k N??ng', 'L??m ?????ng',
        ];

        return view('trips.create', [
            'cities' => $cities
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // N???u kh??ng ph???i admin t???ng th?? kh??ng ???????c th??m chuy???n xe kh??ng ph???i thu???c nh?? xe c???a m??nh
        if (Auth::user()->IdNX != NULL && Bus::where('So_xe', $request->So_xe)->first()->IdNX != Auth::user()->IdNX)
            return redirect()->route('trips.index')->with('error', 'B???n kh??ng th??? th???c hi???n thao t??c n??y!');


        // Validate
        $request->validate([
            'DiemDi' => 'required|different:DiemDen',
            'DiemDen' => 'required|different:DiemDi',
            'XuatPhat' => 'required|after_or_equal:today',
            'Den' => 'required|after:XuatPhat',
            'So_xe' => 'required|regex:/^[0-9]{2}[A-Za-z]{1}-[0-9]{4,5}$/|exists:buses,So_xe',
            'GiaVe' => 'required|numeric|gt:0',
        ]);

        // Ki???m tra xem xe ???? c?? chuy???n trong kho???ng th???i gian ???? hay kh??ng
        $trips = DB::table('trips')
            ->where('IdXe', DB::table('buses')->where('So_xe', $request->So_xe)->first()->IdXe)
            ->get();
        foreach ($trips as $trip) {
            // L???ch tr??nh c???a chuy???n ??i ???? c??
            $XuatPhat = date('Y-m-d H:i:s', strtotime($request->XuatPhat));
            $Den = date('Y-m-d H:i:s', strtotime($request->Den));

            // L???ch tr??nh c???a chuy???n ??i ??ang x??t
            $departure = date('Y-m-d H:i:s', strtotime("$trip->NgayDi $trip->GioDi"));
            $arrival = date('Y-m-d H:i:s', strtotime("$trip->NgayDen $trip->GioDen"));

            // Ki???m tra xem c?? tr??ng l???ch tr??nh kh??ng

            if (($departure >= $XuatPhat && $departure <= $Den) || ($arrival >= $XuatPhat && $arrival <= $Den) || ($departure <= $XuatPhat && $arrival >= $Den))
                return redirect()->route('trips.create')->with('message', 'Xe ???? c?? chuy???n ??i trong kho???ng th???i gian n??y!');
        }


        $IdTuyen = DB::table('bus_routes')->where('TenTuyen', $request->DiemDi . ' - ' . $request->DiemDen)->first()->IdTuyen;

        // Insert
        $request->merge([
            'IdXe' => DB::table('buses')->where('So_xe', $request->So_xe)->first()->IdXe,
            'IdTuyen' => $IdTuyen,
            'GioDi' => date('H:i:s', strtotime($request->XuatPhat)),
            'GioDen' => date('H:i:s', strtotime($request->Den)),
            'NgayDi' => date('Y-m-d', strtotime($request->XuatPhat)),
            'NgayDen' => date('Y-m-d', strtotime($request->Den)),
        ]);

        $count = DB::table('trips')->count() + 1;
        while (true) {
            $check = DB::table('trips')->where('IdChuyen', 'T' . $count)->first();
            if ($check == null)
                break;
            $count++;
        }


        Trip::create([
            'IdChuyen' => 'T' . $count,
            'IdTuyen' => $request->IdTuyen,
            'NgayDi' => $request->NgayDi,
            'GioDi' => $request->GioDi,
            'NgayDen' => $request->NgayDen,
            'GioDen' => $request->GioDen,
            'IdXe' => $request->IdXe,
            'GiaVe' => $request->GiaVe,
        ]);

        // dd($request->all());

        return redirect()->route('trips.index')
            ->with('message', 'T???o chuy???n xe th??nh c??ng.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $IdChuyen
     * @return \Illuminate\Http\Response
     */
    public function show($IdChuyen)
    {
        // L???y th??ng tin chuy???n xe
        $trip = DB::table('trips')
            ->join('buses', 'trips.IdXe', '=', 'buses.IdXe')
            ->join('bus_routes', 'trips.IdTuyen', '=', 'bus_routes.IdTuyen')
            ->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')
            ->select('trips.*', 'buses.So_xe', 'bus_routes.TenTuyen', 'bus_companies.Ten_NX', 'buses.So_Cho_Ngoi')
            ->where('trips.IdChuyen', $IdChuyen)
            ->first();

        // L???y s??? v?? ???? ?????t
        $reservedSeats = DB::table('ticket_details')
            ->join('tickets', 'ticket_details.IdBanVe', '=', 'tickets.IdBanVe')
            ->where('tickets.IdChuyen', $IdChuyen)
            ->where('ticket_details.TinhTrangVe', '!=', '???? h???y')
            ->count();

        // L???y ??i???m ????n
        $DiemDon = DB::table('bus_routes')
            ->join('stops', 'bus_routes.DiaDiemDi', '=', 'stops.DiaDiemDi')
            ->where('bus_routes.IdTuyen', $trip->IdTuyen)
            ->select('stops.*')
            ->get()
            ->toArray();

        return view('trips.show', [
            'trip' => $trip,
            'availableSeats' => $trip->So_Cho_Ngoi - $reservedSeats,
            'DiemDon' => $DiemDon,

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $IdChuyen
     * @return \Illuminate\Http\Response
     */
    public function edit($IdChuyen)
    {
        // Ki???m tra xem ng?????i d??ng c?? ph???i l?? nh?? xe c???a chuy???n xe n??y kh??ng ho???c l?? qu???n tr??? vi??n h??? th???ng
        if (Auth::user()->IdNX != NULL && Auth::user()->IdNX != Trip::join('buses', 'trips.IdXe', '=', 'buses.IdXe')->where('Trips.IdChuyen', $IdChuyen)->select('buses.IdNX')->first()->IdNX)
            return redirect()->route('trips.show', $IdChuyen)->with('error', 'B???n kh??ng th??? th???c hi???n thao t??c n??y!');


        // N???u ng??y gi??? ??i c???a chuy???n ???? qua hi???n t???i th?? kh??ng cho s???a 
        $trip = Trip::find($IdChuyen);
        if (strtotime($trip->NgayDi . ' ' . $trip->GioDi) < strtotime(date('Y-m-d H:i:s')))
            return redirect()->route('trips.show', $IdChuyen)->with('error', 'Chuy???n xe n??y ???? kh???i h??nh/ ???? ho??n th??nh. B???n kh??ng th??? s???a!');


        $cities = [
            'H??a B??nh', 'S??n La', '??i???n Bi??n', 'Lai Ch??u', 'L??o Cai', 'Y??n B??i', 'Ph?? Th???', 'H?? Giang', 'Tuy??n Quang', 'Cao B???ng', 'B???c K???n', 'Th??i Nguy??n', 'L???ng S??n', 'B???c Giang', 'Qu???ng Ninh', 'H?? N???i', 'B???c Ninh', 'H?? Nam', 'H???i D????ng', 'H???i Ph??ng', 'H??ng Y??n', 'Nam ?????nh', 'Th??i B??nh', 'V??nh Ph??c', 'Ninh B??nh', 'Thanh H??a', 'Ngh??? An', 'H?? T??nh', 'Qu???ng B??nh', 'Qu???ng Tr???', 'Hu???', '???? N???ng', 'Qu???ng Nam', 'Qu???ng Ng??i', 'B??nh ?????nh', 'Ph?? Y??n', 'Kh??nh H??a', 'Ninh Thu???n', 'B??nh Thu???n', 'TP. H??? Ch?? Minh', 'V??ng T??u', 'B??nh D????ng', 'B??nh Ph?????c', '?????ng Nai', 'T??y Ninh', 'An Giang', 'B???c Li??u', 'B???n Tre', 'C?? Mau', 'C???n Th??', '?????ng Th??p', 'H???u Giang', 'Ki??n Giang', 'Long An', 'S??c Tr??ng', 'Ti???n Giang', 'Tr?? Vinh', 'V??nh Long', 'Kon Tum', 'Gia Lai', '?????k L???k', '?????k N??ng', 'L??m ?????ng',
        ];

        $cities = sort($cities);

        $Tuyen = DB::table('trips')
            ->join('bus_routes', 'trips.IdTuyen', '=', 'bus_routes.IdTuyen')
            ->select('bus_routes.TenTuyen')
            ->where('trips.IdChuyen', $IdChuyen)
            ->first();
        $DiemDi = explode(' - ', $Tuyen->TenTuyen)[0];
        $DiemDen = explode(' - ', $Tuyen->TenTuyen)[1];

        $trip = DB::table('trips')
            ->join('buses', 'trips.IdXe', '=', 'buses.IdXe')
            ->join('bus_routes', 'trips.IdTuyen', '=', 'bus_routes.IdTuyen')
            ->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')
            ->select('trips.*', 'buses.So_xe', 'bus_routes.TenTuyen', 'bus_companies.Ten_NX', 'buses.So_Cho_Ngoi')
            ->where('trips.IdChuyen', $IdChuyen)
            ->first();

        $XuatPhat = date('Y-m-d H:i:s', strtotime("$trip->NgayDi $trip->GioDi"));
        $Den = date('Y-m-d H:i:s', strtotime("$trip->NgayDen $trip->GioDen"));
        return view('trips.edit', [
            'trip' => $trip,
            'cities' => $cities,
            'DiemDi' => $DiemDi,
            'DiemDen' => $DiemDen,
            'XuatPhat' => $XuatPhat,
            'Den' => $Den,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $IdChuyen
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $IdChuyen)
    {
        // Validate
        $request->validate([
            'DiemDi' => 'required|different:DiemDen',
            'DiemDen' => 'required|different:DiemDi',
            'XuatPhat' => 'required|after_or_equal:today',
            'Den' => 'required|after:XuatPhat',
            'So_xe' => 'required|regex:/^[0-9]{2}[A-Za-z]{1}-[0-9]{4,5}$/|exists:buses,So_xe',
            'GiaVe' => 'required|numeric|gt:0',
        ]);

        $IdTuyen = DB::table('bus_routes')->where('TenTuyen', $request->DiemDi . ' - ' . $request->DiemDen)->first()->IdTuyen;

        // Insert
        $request->merge([
            'IdXe' => DB::table('buses')->where('So_xe', $request->So_xe)->first()->IdXe,
            'IdTuyen' => $IdTuyen,
            'GioDi' => date('H:i:s', strtotime($request->XuatPhat)),
            'GioDen' => date('H:i:s', strtotime($request->Den)),
            'NgayDi' => date('Y-m-d', strtotime($request->XuatPhat)),
            'NgayDen' => date('Y-m-d', strtotime($request->Den)),
        ]);

        Trip::where('IdChuyen', $IdChuyen)
            ->update([
                'IdTuyen' => $request->IdTuyen,
                'NgayDi' => $request->NgayDi,
                'GioDi' => $request->GioDi,
                'NgayDen' => $request->NgayDen,
                'GioDen' => $request->GioDen,
                'IdXe' => $request->IdXe,
                'GiaVe' => $request->GiaVe,
            ]);

        // dd($request->all());

        return redirect()->route('trips.show', $IdChuyen)
            ->with('message', 'S???a th??ng tin chuy???n xe th??nh c??ng.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $IdChuyen
     * @return \Illuminate\Http\Response
     */
    public function destroy($IdChuyen)
    {
        if (Auth::user()->IdNX != NULL && Auth::user()->IdNX != Trip::join('buses', 'trips.IdXe', '=', 'buses.IdXe')->where('Trips.IdChuyen', $IdChuyen)->select('buses.IdNX')->first()->IdNX)
            return redirect()->route('trips.show', $IdChuyen)->with('error', 'B???n kh??ng th??? th???c hi???n thao t??c n??y!');

        // x??a c??c v?? ???? b??n c???a chuy???n xe
        // $tickets l?? m???ng c??c object ?????i di???n cho h??a ????n c???a chuy???n xe
        $tickets = DB::table('tickets')->where('IdChuyen', $IdChuyen)->get()->toArray();
        foreach ($tickets as $ticket)
            DB::table('ticket_details')->where('IdBanVe', $ticket->IdBanVe)->delete();

        // x??a h??a ????n c???a c??c v?? ???? b??n c???a chuy???n xe
        DB::table('tickets')->where('IdChuyen', $IdChuyen)->delete();

        // x??a chuy???n xe
        DB::table('trips')->where('IdChuyen', $IdChuyen)->delete();

        return redirect()->route('trips.index')
            ->with('message', 'X??a chuy???n xe th??nh c??ng.');
    }
}
