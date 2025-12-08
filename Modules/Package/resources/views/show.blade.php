@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('package.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Package
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <div class="mt-2 mb-5">
                <table class="table table-lg table-hover table-borderless bg-white">
                    <thead class="border-bottom">
                        <tr>
                            <th scope="col" class="text-left">
                                <div class="mb-5">
                                    <div class="h5 font-weight-bold">Fitur</div>
                                </div>
                            </th>
                            <th scope="col" class="text-center text-nowrap">
                                <div class="h5 font-weight-bold mb-0">{{ $data['package']->name }}</div>
                                <p class="font-weight-normal text-muted">{{ $data['package']->price ? "Rp. ".number_format($data['package']->price, 0, ',', '.'). " / " : '' }} {{ $data['package']->duration . " " . $data['package']->duration_type }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['features'] as $i => $feature)
                            <tr class="border-bottom bg-light">
                                <th scope="row" class="h6 py-3 font-weight-semibold text-nowrap border-0 mb-0">{{ $feature->name }}</th>
                                <td class="py-3 border-0"></td>
                                <td class="py-3 border-0"></td>
                                <td class="py-3 border-0"></td>
                                <td class="py-3 border-0"></td>
                            </tr>
                            @foreach ($feature->childs as $j => $sub)
                                <tr>
                                    <th class="font-weight-normal" scope="row">{{ $sub->name }}</th>
                                    <td class="text-center">
                                        @if(isset($data['rules'][$sub->id]) && $data['rules'][$sub->id]['pivot']['limit'])
                                            {{ $data['rules'][$sub->id]['pivot']['limit'] == -1 ? "Unlimited" : $data['rules'][$sub->id]['pivot']['limit'] }}

                                            @if ($data['rules'][$sub->id]['pivot']['limit_type'] == 'max')
                                                Maks data
                                            @elseif ($data['rules'][$sub->id]['pivot']['limit_type'] == 'day')
                                                Hari
                                            @elseif ($data['rules'][$sub->id]['pivot']['limit_type'] == 'time')
                                                X
                                            @elseif ($data['rules'][$sub->id]['pivot']['limit_type'] == 'month')
                                                Perbulan
                                            @endif
                                        @else
                                            <i class='mdi mdi-18px {{ isset($data['rules'][$sub->id]) && $data['rules'][$sub->id]['pivot']['included'] == true ? "mdi-checkbox-marked text-success" : "mdi-close-box text-danger" }}'></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-right">
                <a href="{{ route('package.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection

