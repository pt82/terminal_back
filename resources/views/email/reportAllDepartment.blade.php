<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<style>
    table {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 14px;
        border-radius: 10px;
        border-spacing: 0;
        text-align: center;
    }
    th {
        background: #AFCDE7;
        color: white;
        text-shadow: 0 1px 1px #2D2020;
        padding: 10px 20px;
    }
    th, td {
        border-style: solid;
        border-width: 0 1px 1px 0;
        border-color: white;
    }
    th:first-child, td:first-child {
        text-align: left;
    }
    th:first-child {
        border-top-left-radius: 10px;
    }
    th:last-child {
        border-top-right-radius: 10px;
        border-right: none;
    }
    td {
        padding: 10px 20px;
        background: #D8E6F3;
    }

</style>
<body>

<h4>
    <h3>Отчет по открытию салонов на {{now()->format('d.m.Y')}} </h3>
    <hr>
    @foreach ($data['currentResult'] as $itemDepartmen=>$key)
        <br>
        {{$itemDepartmen}}
        <table >
            <th>ФИО</th>
            <th>Начало</th>
              @foreach ($key as $inner_key => $value)
                <tr>
                    <td> {{ $value['name'] }}</td>
                    <td> {{ $value['start_time'] }}</td>
                </tr>
           @endforeach
        </table>
    @endforeach
    <br>
    <h3> Отчет по закрытию салонов на {{(new DateTime('-1 day'))->format('d.m.Y')}}</h3>
    <hr>
    @foreach ($data['previousResult'] as $itemDepartmen=>$key)
        <br>
        {{$itemDepartmen}}
        <table >
            <th>ФИО</th>
            <th>Окончание</th>
            @foreach ($key as $inner_key => $value)
                <tr>
                    <td> {{ $value['name'] }}</td>
                    <td> {{ $value['end_time'] }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

</h4>


</body>
</html>
