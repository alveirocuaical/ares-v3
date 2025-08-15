<template>
<div>
    <div class="page-header pr-0">
        <h2><a href="/co-configuration-change-ambient">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                </path>
            </svg>
        </a></h2>
        <ol class="breadcrumbs">
            <li class="active"><span>Cambiar Ambiente de Operacion - (HABILITACION - PRODUCCION)</span></li>
        </ol>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
        <!-- <div class="card-header bg-info">
            <h3 class="my-0">Cambiar Ambiente de Operacion - (HABILITACION - PRODUCCION)</h3>
        </div> -->
        <div class="card-body">
            <div class="invoice">
                <form>
                    <div class="form-body">
                        <div class="row mt-4 mb-4">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="control-label">ResponseDian <small>(Seleccione de aqui la llave tecnica.)</small></label>
                                    <el-input
                                        type="textarea"
                                        class="custom-textarea"
                                        v-model="production.technicalkey">
                                    </el-input>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions text-right mt-4">
                            <div>
                                <h4 class="d-inline mr-3 font-weight-bold">Facturación: </h4>
                                <el-button :loading="loadingCompany" class="submit" type="primary" @click="validateProduction('H')" >Pasar a Habilitación</el-button>
                                <el-button :loading="loadingCompany" class="submit" type="primary" @click="validateProduction('P')" >Pasar a Producción</el-button>
                            </div>
                            <div class="mt-4">
                                <h4 class="d-inline mr-3 font-weight-bold">Nómina: </h4>
                                <el-button :loading="loadingPayroll" class="submit" type="primary" @click="validateProduction('payrollH')" >Pasar a Habilitación</el-button>
                                <el-button :loading="loadingPayroll" class="submit" type="primary" @click="validateProduction('payrollP')" >Pasar a Producción</el-button>
                            </div>
                            <div class="mt-4">
                                <h4 class="d-inline mr-3 font-weight-bold">Documentos Equivalentes: </h4>
                                <el-button :loading="loadingEqDocs" class="submit" type="primary" @click="validateProduction('eqdocsH')" >Pasar a Habilitación</el-button>
                                <el-button :loading="loadingEqDocs" class="submit" type="primary" @click="validateProduction('eqdocsP')" >Pasar a Producción</el-button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</template>

<style>
    .custom-textarea .el-textarea__inner textarea {
        height: 350px;
    }
</style>

<script>
    import Helper from "../../../mixins/Helper";

    export default {
        mixins: [Helper],
        data: () => ({
            loadingCompany: false,
            production: { technicalkey: ''},
            route: 'co-configuration/production',
            loadingPayroll: false,
            loadingEqDocs: false
        }),

        methods: {
            validateProduction(environment) {
                if(['P', 'H'].includes(environment))
                    this.loadingCompany = true;
                else
                    if(['payrollP', 'payrollH'].includes(environment))
                        this.loadingPayroll = true;
                    else
                        this.loadingEqDocs = true;

                axios
                    .post(`${this.route}/changeEnvironmentProduction/${environment}`)
                    .then(response => {
                        this.$message.success(response.data)
                    })
                    .catch(error => {
                        this.$message.error(error.response.data)
                    })
                    .then(() => {
                        this.loadingCompany = false;
                        this.loadingPayroll = false;
                        this.loadingEqDocs = false;
                    });

                if(environment == 'P'){
                    this.loadingCompany = true;
                    axios
                        .post(`${this.route}/queryTechnicalKey`)
                        .then(response => {
    //                        this.$setLaravelMessage(response.data);
                            if(response.data.success){
                                this.production.technicalkey = JSON.stringify(response.data, null, 2)
                            }
                            else{
                                this.$message.error(response.data.message)
                            }
                        })
                        .catch(error => {
                        // this.$setLaravelValidationErrorsFromResponse(error.response.data);
                            //this.$setLaravelErrors(error.response.data);
                            this.$message.error(error.response.data)
                        })
                        .then(() => {
                            this.loadingCompany = false;
                        });
                }
                else
                    this.production.technicalkey = "No se pueden consultar claves tecnicas para ambiente de HABILITACION."
            }
        }
    };
</script>
