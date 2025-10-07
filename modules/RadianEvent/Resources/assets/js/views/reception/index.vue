<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/co-radian-events/reception">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-check" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6"></path><path d="M16 3v4"></path><path d="M8 3v4"></path><path d="M4 11h16"></path><path d="M15 19l2 2l4 -4"></path></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Subir documento</span></li>
            </ol>
            <div class="right-wrapper pull-right">
            </div>
        </div>
        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Subir documento</h3>
            </div> -->
            <div class="card-body">
                <form autocomplete="off" @submit.prevent="submit">
                    <div class="col-md-12 mt-4">
                        <div class="form-group text-center" >
                            <el-upload
                                    ref="upload"
                                    :headers="headers"
                                    action="/co-radian-events/upload"
                                    :show-file-list="true"
                                    :auto-upload="false"
                                    :multiple="false"
                                    :on-error="errorUpload"
                                    :limit="1"
                                    :on-success="successUpload">
                                <el-button slot="trigger" type="primary">Seleccione un archivo (xml/pdf)</el-button>
                            </el-upload>
                        </div>
                    </div>

                    <div class="form-actions text-right mt-4">
                        <el-button type="primary" native-type="submit">Procesar</el-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script>


    export default {
        data() {
            return {
                resource: 'items',
                headers: headers_token,
            }
        },
        created() {
        },
        methods: {
            async submit() {
                await this.$refs.upload.submit()
            },
            successUpload(response, file, fileList) {
                console.log(response)
                if (response.success) {
                    this.$message.success(response.message)
                    this.$refs.upload.clearFiles()
                } else {
                    this.$message({message:response.message, type: 'error'})
                }
            },
            errorUpload(response) {
                console.log(response)
            }
        }
    }
</script>
