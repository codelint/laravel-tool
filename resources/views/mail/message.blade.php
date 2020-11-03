<?php date_default_timezone_set('Asia/Shanghai'); ?>
@if(!isset($detail))
    <h3>{{ isset($mail_message) ? $mail_message : '' }}</h3>
@else
    @if(is_array($detail))
        <div style="padding: 4px">
            <table style="border-collapse: collapse;font-size: 15px;width: 100%;">
                <tbody>
                @foreach($detail as $k => $v)
                    <tr style="border: solid 1px;">
                        <td style="vertical-align:top;width:20%;padding: 4px;text-align: right;font-weight: bold;background-color: #f8f8f8">{{ $k }}</td>
                        <td style="vertical-align:top;padding: 4px;text-align: left">{{ is_string($v) ? $v : json_encode(value($v)) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="font-weight: bold">
            {!! isset($detail) ? $detail : '' !!}
        </div>
    @endif

    @if(isset($meta))
    <div style="padding: 4px">
        <table style="border-collapse: collapse;font-size: 15px;width: 100%;">
            <tbody>
            @foreach($meta as $k => $v)
            <tr style="border: solid 1px;">
                <td style="vertical-align:top;width:20%;padding: 4px;text-align: right;font-weight: bold;background-color: #f8f8f8">{{ $k }}</td>
                <td style="vertical-align:top;padding: 4px;text-align: left">{{ value($v) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endif
