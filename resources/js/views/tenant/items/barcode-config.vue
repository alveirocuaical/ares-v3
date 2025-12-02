<template>
  <el-dialog
    :visible.sync="localShow"
    :width="dialogWidth"
    title="Configurar Etiquetas"
    @close="handleClose"
    custom-class="barcode-config-dialog"
  >
    <div class="barcode-config-content">
      <!-- Configuración de Tirilla -->
      <div class="form-group mb-4">
        <label class="font-weight-bold">Configuración de Tirilla</label>
        <div class="row">
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="width"
              :min="20"
              :max="100"
              label="Ancho"
              controls-position="right"
              placeholder="Ancho"
            />
            <small class="text-muted">Ancho (mm)</small>
          </div>
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="height"
              :min="10"
              :max="100"
              label="Alto"
              controls-position="right"
              placeholder="Alto"
            />
            <small class="text-muted">Alto (mm)</small>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="gapX"
              :min="0.1"
              :max="5"
              label="Espaciado"
              controls-position="right"
              placeholder="Espaciado"
            />
            <small class="text-muted">Espaciado</small>
          </div>
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="repeat"
              :min="1"
              :max="500"
              label="Cantidad"
              controls-position="right"
              placeholder="Cantidad"
              :disabled="useStockAsRepeat"
            />
            <small class="text-muted">Cantidad</small>
          </div>
        </div>
      </div>

      <!-- Configuración de Hoja -->
      <div class="form-group mb-4">
        <label class="font-weight-bold">Configuración de Hoja</label>
        <div class="row">
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="pageWidth"
              :min="50"
              :max="300"
              label="Ancho hoja"
              controls-position="right"
              placeholder="Ancho hoja"
            />
            <small class="text-muted">Ancho hoja (mm)</small>
          </div>
          <div class="col-6">
            <el-input-number
              class="w-100"
              v-model="columns"
              :min="1"
              :max="3"
              label="Columnas"
              controls-position="right"
              placeholder="Columnas"
            />
            <small class="text-muted">Columnas</small>
          </div>
        </div>
      </div>

      <!-- Configuraciones Adicionales -->
      <div class="form-group mb-4">
        <label class="font-weight-bold">Configuraciones Adicionales</label>
        <div class="row">
          <div class="col-6">
            <el-switch
              v-model="codeType"
              active-value="qr"
              inactive-value="barcode"
              active-text="QR"
              inactive-text="Barras"
            ></el-switch>
          </div>
          <div class="col-6">
            <el-switch
              v-model="useEstablishmentName"
              active-text="Establecimiento"
              inactive-text="Empresa"
            ></el-switch>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <label class="font-weight-bold">Campos a Mostrar</label>
            <div class="row">
              <div class="col-6 col-md-4 mb-2" v-for="(field, key) in fields" :key="key">
                <el-checkbox v-model="fields[key]">{{ fieldLabels[key] }}</el-checkbox>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón de acción -->
      <div class="form-group d-flex justify-content-end">
        <el-button type="success" @click="printLabel">
          Imprimir
        </el-button>
      </div>
    </div>
  </el-dialog>
</template>

<script>
export default {
  props: {
    show: Boolean,
    itemId: [Number, Array],
    stock: {
      type: [Number, Array, String],
      default: null,
    },
    fromPurchase: {
      type: Boolean,
      default: false,
    }
  },
  data() {
    return {
      localShow: this.show,
      width: 32,
      height: 25,
      pageWidth: 100,
      columns: 3,
      gapX: 2,
      repeat: 15,
      useStockAsRepeat: false,
      fields: {
        price: true,
        brand: true,
        category: false,
        color: false,
        size: false,
      },
      fieldLabels: {
        price: 'Precio',
        brand: 'Marca',
        category: 'Categoría',
        color: 'Color',
        size: 'Talla',
      },
      dialogWidth: '600px',
      codeType: 'barcode',
      useEstablishmentName: false,
    };
  },
  watch: {
    show(val) {
      this.localShow = val;
      // Inicializar valores al abrir para evitar estados previos
      if (val) {
        this.useStockAsRepeat = false;
        // Si es un solo item y hay stock, prellenar repeat con el stock; si no, dejar 1
        if (!Array.isArray(this.itemId) && (typeof this.stock === 'number' || typeof this.stock === 'string')) {
          this.repeat = parseInt(this.stock) || 1;
        } else {
          this.repeat = 1;
        }
      }
    },
    localShow(val) {
      if (!val) this.$emit('update:show', false);
    },
    itemId() {
      if (this.useStockAsRepeat) this.applyStockToRepeat();
    },
    stock() {
      if (this.useStockAsRepeat) this.applyStockToRepeat();
    }
  },
  mounted() {
    this.setDialogWidth();
    window.addEventListener('resize', this.setDialogWidth);
    if (this.useStockAsRepeat) this.applyStockToRepeat();
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.setDialogWidth);
  },
  computed: {
    // Mostrar checkbox para múltiples items solo si todos tienen stock > 0
    showMultiUseStock() {
      if (Array.isArray(this.itemId) && Array.isArray(this.stock) && this.itemId.length > 0) {
        return this.stock.every(s => parseFloat(s) > 0);
      }
      return false;
    },
    // Mostrar checkbox para item único solo si stock > 0
    showSingleUseStock() {
      // Solo muestra el checkbox si el stock es mayor a 0
      return !Array.isArray(this.itemId) && (parseFloat(this.stock) > 0);
    },
    // Etiqueta legible para el checkbox de stock único
    singleStockLabel() {
      if (this.stock === null || this.stock === undefined) return '0';
      const n = parseFloat(this.stock);
      return Number.isFinite(n) ? (n % 1 === 0 ? String(n) : n.toFixed(2)) : String(this.stock);
    }
  },
  methods: {
    onUseStockAsRepeat(val) {
      this.useStockAsRepeat = val;
      if (val) {
        this.applyStockToRepeat();
      } else {
        // restablecer a 1 o a un valor razonable
        this.repeat = 1;
      }
    },
    applyStockToRepeat() {
      if (this.useStockAsRepeat) {
        // Si vienen varios items y stock es array -> CSV en mismo orden
        if (Array.isArray(this.itemId) && Array.isArray(this.stock)) {
          const idsLen = this.itemId.length;
          const stocks = this.stock.slice(0, idsLen);
          while (stocks.length < idsLen) stocks.push(0);
          this.repeat = stocks.join(',');
          return;
        }
        // Si es un solo item con stock numérico
        if (!Array.isArray(this.itemId) && (typeof this.stock === 'number' || typeof this.stock === 'string')) {
          const n = parseInt(this.stock) || 0;
          this.repeat = n > 0 ? n : 0;
          return;
        }
      }
      // Si no está activo el checkbox, no tocar el repeat (deja el valor manual)
    },
    setRepeatToStock() {
      if (this.stock > 0) {
        this.repeat = this.stock;
      }
    },
    handleClose() {
      this.localShow = false;
    },
    getPrintUrl() {
      let repeatValue = this.repeat;
      // Si hay varios productos y NO se usa el checkbox, repetir la cantidad para cada producto
      if (Array.isArray(this.itemId) && !this.useStockAsRepeat) {
        // Si repeat es un número, conviértelo a CSV para todos los productos
        const qty = parseInt(this.repeat) || 1;
        repeatValue = Array(this.itemId.length).fill(qty).join(',');
      }
      const params = new URLSearchParams({
        width: this.width,
        height: this.height,
        pageWidth: this.pageWidth,
        columns: this.columns,
        gapX: this.gapX,
        repeat: repeatValue,
        codeType: this.codeType,
        use_establishment: this.useEstablishmentName,
        ...this.fields,
      }).toString();

      if (Array.isArray(this.itemId)) {
        // Para varios IDs
        return `/items/barcodes?ids=${this.itemId.join(',')}&${params}`;
      } else {
        // Para un solo ID
        return `/items/barcode-label/${this.itemId}?${params}`;
      }
    },
    printLabel() {
      if (this.fromPurchase) {
            // Emite evento al padre para imprimir con cantidades de compra
            this.$emit('print-purchase-labels', {
                width: this.width,
                height: this.height,
                pageWidth: this.pageWidth,
                columns: this.columns,
                gapX: this.gapX,
                fields: this.fields,
                codeType: this.codeType,
            });
        } else {
            window.open(this.getPrintUrl(), '_blank');
        }
    },
    setDialogWidth() {
      this.dialogWidth = window.innerWidth < 700 ? '95%' : '600px';
    }
  },
};
</script>

<style scoped>
.barcode-config-dialog .el-dialog {
  max-width: 98vw;
  padding: 0 !important;
}
.barcode-config-content {
  padding: 0 10px 0px 10px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
@media (max-width: 700px) {
  .barcode-config-dialog .el-dialog {
    margin: 10px auto !important;
  }
}
.label-barcode {
  color: #222;
  letter-spacing: 0.5px;
  text-align: right;
}
</style>