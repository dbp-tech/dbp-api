<html>
<head>
    <style>
        @page {
            size: 94mm 150mm;
        }

        table {
            border-collapse: collapse;
        }
        table, th, td {
            border: 2px solid black;
            font-size: 13px;
        }
        .kiri-kanan{
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
    <title>Document</title>
</head>
<body>
        <div style="width: 350px; height: 500px; margin-bottom: 5rem; display: inline-block">
            <div style="padding: 2px">
                <table width="100%" height="100%">
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <h5><b>Catatan :
                                        {{--{{$detail->note}}--}}
                                    </b></h5>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
</body>
</html>
