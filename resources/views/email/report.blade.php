<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

<h4>
   <h3>Отчет на {{date('d.m.Y')}} </h3>
    <hr>
    <table border="1">
        <th>Фамилия</th>
        <th>Имя</th>
        <th>Отчество</th>
        <th>Начало</th>
        <th>Окончание</th>
    @foreach ($data as $item)
                        <tr>
                            <td> {{ $item['firstname'] }}</td>
                            <td> {{ $item['lastname'] }}</td>
                            <td> {{ $item['fatherland'] }}</td>
                            <td> {{ $item['start_time'] }}</td>
                            <td> {{ $item['end_time'] }}</td>
                    </tr>
            @endforeach
   </table>


</h4>


</body>
</html>
