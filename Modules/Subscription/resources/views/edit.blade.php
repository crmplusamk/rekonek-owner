@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('subscription.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Edit Subscription
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <form action="{{ route('subscription.update', $data->id) }}" method="post">
                @csrf
                @method('put')
                <h3 class="element-header mt-3">Customer</h3>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label>Customer <span style="color: #DB405A;">*</span></label>
                            <select class="form-control select-customer" name="customer" data-validator="required" data-validator-label="customer">
                                <option value="{{ $data->customer->id }}" selected>{{ $data->customer->name }}</option>
                            </select>
                            <div class="form-control-feedback"></div>
                            @error('customer')
                                <div class="text-danger text-sm mt-2">{{ $errors->first('customer')}}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <h3 class="element-header mt-3">Package</h3>
                <div class="row">
                    <div class="col-12 col-md-12">
                        <div class="table-responsive border-1 rounded-0">
                            <table class="table datatables" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="text-center">Package</th>
                                        <th class="text-center">Durasi</th>
                                        <th class="text-center">Harga</th>
                                        <th class="text-center">Diskon %</th>
                                        <th class="text-center">Diskon Rp.</th>
                                        <th class="text-center">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="entry">
                                        <td class="p-0 border-top-0" style="min-width: 250px">
                                            <select class="form-control selectPackage" name="package" data-validator="required" data-validator-label="paclage" required>
                                                <option value="{{ $data->package->id }}" selected>{{ $data->package->name }}</option>
                                            </select>
                                            <div class="form-control-feedback"></div>
                                        </td>
                                        <td class="p-0 border-top-0">
                                            <input name="duration" type="text" placeholder="0" value="{{ $data->package->duration."-".$data->package->duration_type }}" class="form-control rounded-0 text-right border-right-0 arrow-none" readonly />
                                            <div class="form-control-feedback"></div>
                                        </td>
                                        <td class="p-0 border-top-0">
                                            <input name="price" type="text" placeholder="0" value="{{ number_format($data->package->price, 0, ',', '.') }}" class="form-control rounded-0 text-right border-right-0 arrow-none" readonly />
                                            <div class="form-control-feedback"></div>
                                        </td>
                                        <td class="p-0 border-top-0">
                                            <input name="discount_percent" type="number" placeholder="0" class="form-control rounded-0 border-right-0 text-right arrow-none" readonly />
                                            <div class="form-control-feedback"></div>
                                        </td>
                                        <td class="p-0 border-top-0">
                                            <input name="discount_amount" type="text" placeholder="0" class="form-control rounded-0 border-right-0 text-right" readonly />
                                            <div class="form-control-feedback"></div>
                                        </td>
                                        <td class="p-0 border-top-0">
                                            <input name="subtotal" type="text" placeholder="0" value="{{ number_format($data->package->price, 0, ',', '.') }}" class="form-control rounded-0 text-right" readonly />
                                            <div class="form-control-feedback"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-right">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>

    $('.selectPackage').select2({
        ajax: {
            method: "get",
            url: "{{ route('package.list') }}",
            dataType: "json",
            data: function(params) {
                return {
                    search: params.term,
                };
            },
            processResults: function(data, params) {
                var formattedData = data.data.map(function(item) {
                    return {
                        id: item.id,
                        text: item.name,
                        price: item.price,
                        duration: item.duration,
                        duration_type: item.duration_type,
                    };
                });

                return {
                    results: formattedData
                };
            },
            cache: true,
        },
        placeholder: "Pilih Package",
    });


    $(".selectPackage").on("select2:select", function(e)
    {
        let data = e.params.data;

        $("[name='duration']").val(`${data.duration} - ${data.duration_type}`);
        $("[name='price']").val(data.price ? formatNumber(data.price, 'none') : 0);
        $("[name='subtotal']").val(data.price? formatNumber(data.price, 'none') : 0);
    });

</script>
@endpush
