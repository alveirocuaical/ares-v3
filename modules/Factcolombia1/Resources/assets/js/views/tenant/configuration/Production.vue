<template>
    <div>
        <!-- ...existing code... -->
        <div class="card mb-0 pt-2 pt-md-0">
            <div class="card-body">
                <div class="invoice">
                    <form>
                        <div class="form-body">
                            <!-- Fila principal con los 3 módulos -->
                            <div class="row">
                                <!-- Facturación -->
                                <div class="col-lg-4 mb-4">
                                    <div class="text-center">
                                        <h5 class="font-weight-bold mb-3">Facturación</h5>
                                        <div class="d-flex flex-column">
                                            <el-button :loading="loadingCompanyH" class="submit mb-2" type="primary"
                                                @click="validateProduction('H')">
                                                Pasar a Habilitación
                                            </el-button>
                                            <el-button :loading="loadingCompanyP" class="submit mb-2" type="primary"
                                                @click="validateProduction('P')">
                                                Pasar a Producción
                                            </el-button>
                                            <el-button class="submit mb-2" type="primary"
                                                @click="openResolutionsModal('invoice')">
                                                Gestionar Resoluciones
                                            </el-button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documentos Equivalentes -->
                                <div class="col-lg-4 mb-4">
                                    <div class="text-center">
                                        <h5 class="font-weight-bold mb-3">Documentos Equivalentes</h5>
                                        <div class="d-flex flex-column">
                                            <el-button :loading="loadingEqDocsH" class="submit mb-2" type="primary"
                                                @click="validateProduction('eqdocsH')">
                                                Pasar a Habilitación
                                            </el-button>
                                            <el-button :loading="loadingEqDocsP" class="submit mb-2" type="primary"
                                                @click="validateProduction('eqdocsP')">
                                                Pasar a Producción
                                            </el-button>
                                            <el-button class="submit mb-2" type="primary"
                                                @click="openResolutionsModal('eqdocs')">
                                                Gestionar Resoluciones
                                            </el-button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nómina -->
                                <div class="col-lg-4 mb-4">
                                    <div class="text-center">
                                        <h5 class="font-weight-bold mb-3">Nómina</h5>
                                        <div class="d-flex flex-column">
                                            <el-button :loading="loadingPayrollH" class="submit mb-2" type="primary"
                                                @click="validateProduction('payrollH')">
                                                Pasar a Habilitación
                                            </el-button>
                                            <el-button :loading="loadingPayrollP" class="submit mb-2" type="primary"
                                                @click="validateProduction('payrollP')">
                                                Pasar a Producción
                                            </el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <resolutions-modal :showDialog.sync="showResolutionsModal" :moduleType="currentModuleType"
            @refresh="handleRefresh">
        </resolutions-modal>
    </div>
</template>

<style>
/* .custom-textarea .el-textarea__inner textarea {
        height: 350px;
    } */
</style>

<script>
import Helper from "../../../mixins/Helper";
import ResolutionsModal from "./partial/ResolutionsModal.vue";

export default {
    mixins: [Helper],
    components: {
        ResolutionsModal
    },
    data: () => ({
        loadingCompany: false,
        production: { technicalkey: '' },
        route: 'co-configuration/production',
        loadingPayroll: false,
        loadingCompanyH: false,
        loadingCompanyP: false,
        loadingPayrollH: false,
        loadingPayrollP: false,
        loadingEqDocsH: false,
        loadingEqDocsP: false,
        loadingEqDocs: false,
        loadingTechnicalKeyInvoice: false,
        loadingTechnicalKeyPayroll: false,
        loadingTechnicalKeyEqdocs: false,
        showResolutionsModal: false,
        currentModuleType: 'invoice'
    }),

    methods: {
        validateProduction(environment) {
            // Resetear todos los loadings
            this.resetLoadingStates();

            // Activar el loading específico
            if (environment === 'H') this.loadingCompanyH = true;
            if (environment === 'P') this.loadingCompanyP = true;
            if (environment === 'payrollH') this.loadingPayrollH = true;
            if (environment === 'payrollP') this.loadingPayrollP = true;
            if (environment === 'eqdocsH') this.loadingEqDocsH = true;
            if (environment === 'eqdocsP') this.loadingEqDocsP = true;

            axios
                .post(`${this.route}/changeEnvironmentProduction/${environment}`)
                .then(response => {
                    this.$message.success(response.data)
                })
                .catch(error => {
                    this.$message.error(error.response.data)
                })
                .then(() => {
                    this.resetLoadingStates();
                });

            // Limpiar el textarea cuando se cambia a habilitación
            if (['H', 'payrollH', 'eqdocsH'].includes(environment)) {
                this.production.technicalkey = "No se pueden consultar claves técnicas para ambiente de HABILITACIÓN.";
            }
        },

        queryTechnicalKey(type) {
            // Activar el loading específico del technical key
            if (type === 'invoice') this.loadingTechnicalKeyInvoice = true;
            if (type === 'payroll') this.loadingTechnicalKeyPayroll = true;
            if (type === 'eqdocs') this.loadingTechnicalKeyEqdocs = true;

            axios
                .post(`${this.route}/queryTechnicalKey`, { type })
                .then(response => {
                    if (response.data.success) {
                        this.production.technicalkey = JSON.stringify(response.data, null, 2)
                    }
                    else {
                        this.$message.error(response.data.message)
                    }
                })
                .catch(error => {
                    this.$message.error(error.response.data)
                })
                .then(() => {
                    this.loadingTechnicalKeyInvoice = false;
                    this.loadingTechnicalKeyPayroll = false;
                    this.loadingTechnicalKeyEqdocs = false;
                });
        },

        resetLoadingStates() {
            this.loadingCompanyH = false;
            this.loadingCompanyP = false;
            this.loadingPayrollH = false;
            this.loadingPayrollP = false;
            this.loadingEqDocsH = false;
            this.loadingEqDocsP = false;
        },
        openResolutionsModal(moduleType) {
            if (moduleType === 'eqdocs') {
                this.currentModuleType = 'eqdocs';
            } else {
                this.currentModuleType = moduleType;
            }
            this.showResolutionsModal = true;
        },

        handleRefresh() {
            this.$refs.resolutionsModal.refreshData();
        }
    }
};
</script>