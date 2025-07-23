<template>
<div class="container p-3 mt-4 main">
    {{events}}
    <button @click="sel" class="btn btn-danger">Выбрать</button>
    <button class="btn btn-secondary">Отключиться</button>
    <hr>
    <h4>Выберите сотрудника</h4>
<!--    <b-form-select v-model="select_persons" >-->
<!--        <option v-for="item in persons" v-bind:value="item.id" :key="item.id">-->
<!--            {{item.description}}-->
<!--        </option>-->
<!--    </b-form-select>-->
    <h4>Выберите дату</h4>
<!--    <datetime class="border" v-model="form.start_time" type="date"></datetime>-->
    <hr>
    <button @click="connect()" class="btn btn-success">Сформировать</button>
<!--    <vue-ads-table-tree-->
<!--        :columns="columns"-->
<!--        :rows="arr"-->
<!--        :page="page"-->
<!--        :items-per-page="5"-->
<!--        @page-change="pageChanged"-->
<!--        @filter-change="filterChanged"-->
<!--    >-->
<!--    </vue-ads-table-tree>-->
</div>
</template>

<script>
// import { DateTime } from 'luxon';
export default {
   name: "App",

data() {
    return {
        form: {
            start_time: null
        },
        events: [],
        select_persons: null,
        persons: [],
        arr: [],
        page:0,
        columns:[
            {
                property: 'track_start',
                title: 'Начало смены',
            }
            ]
    }
},
    computed: {

},
    mounted() {
        this.person_all()
    },
    methods: {
    sel() {


    },
    person_all(){
        return axios
            .post(`http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`,{"limit": 100, "face_galleries":["100-GVaGUwCF2mHejrHbKykm"]})
            .then(response => {
                this.persons=response.data.result.items
            })
    },
    connect() {
         this.arr.length=0
        // var asiaTime = new Date().toLocaleString("en-US", {timeZone: "Asia/Novosibirsk"});

        let start_time=+new Date(this.form.start_time)/1000

       return axios
          //  персонал  .post(`http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`,{"limit": 100, "face_galleries":["100-GVaGUwCF2mHejrHbKykm"]})
          //    события .post(`http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`,{"limit": 1000, "start_time":start_time,  "faces":[this.select_persons]})
          //     .post(`api/persons`,{"start_time":start_time,  "faces":[this.select_persons]}) //работает id сотрудника
              .post(`api/persons`, {"faces":[this.select_persons]})
          //    .post(`http://openapi-alpha-eu01.ivideon.com/face_galleries?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
            // .post(`http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
             // .post(`http://openapi-alpha-eu01.ivideon.com/face_stats?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
             // .post(`http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
           //   .post(`http://openapi-alpha-eu01.ivideon.com/cameras?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
            // .post(`http://openapi-alpha-eu01.ivideon.com/cameras/100-MmGJshcLzl2meHPvu1GQsp:0/archive_calendar?op=GET&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b`)
            .then(response => {
                this.events=response
                console.log(response.data.result.items)
                //  let arr=response.data.result.items[response.data.result.items.length-1]
                // var asiaTime = new Date(arr.track_start*1000).toLocaleString("en-US", {timeZone: "Asia/Novosibirsk"});
                // let dateTime = new Date(arr.track_start*1000).toUTCString();
                //  arr.track_start=this.$moment(dateTime).utcOffset(7).format("DD.MM.YYYY HHч mmмин");
                //   this.arr.push(arr)

                })
    },
        pageChanged (page) {
            this.page = page;
        },
        filterChanged (filter) {
            this.filter = filter;
        },

    }

}
</script>

<style scoped lang="scss">
.main{
    background-color: lightgrey;
}
</style>
