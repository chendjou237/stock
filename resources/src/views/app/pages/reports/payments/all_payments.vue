<template>
  <div class="main-content">
    <breadcumb :page="$t('PaymentsReport')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <div class="row" v-if="!isLoading">
      <div class="col-md-12 mb-3">
        <div class="card">
          <div class="card-header">
            <h3>{{ $t('PaymentsReport') }}</h3>
            <div class="dt-buttons btn-group flex-wrap">
              <b-button @click="Payment_PDF()" size="sm" variant="outline-success ripple m-1">
                <i class="i-File-Copy"></i> PDF
              </b-button>
              <vue-excel-xlsx
                class="btn btn-sm btn-outline-danger ripple m-1"
                :data="payments"
                :columns="columns"
                :file-name="'payments'"
                :file-type="'xlsx'"
                :sheet-name="'payments'"
              >
                <i class="i-File-Excel"></i> EXCEL
              </vue-excel-xlsx>
            </div>
          </div>
          <div class="card-body">
            <div class="mt-3 mb-4">
              <b-row>
                <!-- Date Range -->
                <b-col md="4" sm="12">
                  <validation-provider name="date range" rules="" v-slot="{ valid, errors }">
                    <b-form-group :label="$t('date_range')">
                      <date-range-picker
                        v-model="dateRange"
                        :startDate="dateRange.startDate"
                        :endDate="dateRange.endDate"
                        @update="Get_All_Payments"
                        :locale-data="{ firstDay: 1, format: 'DD-MM-YYYY' }"
                        :ranges="true"
                      >
                        <template v-slot:input="picker" >
                          {{ picker.startDate.toJSON().slice(0, 10) }} - {{ picker.endDate.toJSON().slice(0, 10) }}
                        </template>
                      </date-range-picker>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Payment Type -->
                <b-col md="4" sm="12">
                  <b-form-group :label="$t('Paymentchoice')">
                    <select v-model="Filter_type" type="text" class="form-control">
                      <option value="">{{ $t('All') }}</option>
                      <option value="sales">{{ $t('PaymentsSales') }}</option>
                      <option value="purchases">{{ $t('PaymentsPurchases') }}</option>
                      <option value="sale_returns">{{ $t('payments_Sales_Return') }}</option>
                      <option value="purchase_returns">{{ $t('payments_Purchases_Return') }}</option>
                    </select>
                  </b-form-group>
                </b-col>

                <!-- Payment Status -->
                <b-col md="4" sm="12">
                  <b-form-group :label="$t('PaymentStatus')">
                    <select v-model="Filter_status" type="text" class="form-control">
                      <option value="">{{ $t('All') }}</option>
                      <option value="paid">{{ $t('Paid') }}</option>
                      <option value="partial">{{ $t('Partial') }}</option>
                      <option value="unpaid">{{ $t('Unpaid') }}</option>
                    </select>
                  </b-form-group>
                </b-col>
              </b-row>
            </div>

            <!-- Payments Table -->
            <vue-good-table
              mode="remote"
              :columns="columns"
              :totalRows="totalRows"
              :rows="payments"
              @on-page-change="onPageChange"
              @on-per-page-change="onPerPageChange"
              @on-sort-change="onSortChange"
              @on-search="onSearch"
              :search-options="{
                enabled: true,
                placeholder: $t('Search_this_table'),
              }"
              :select-options="{
                enabled: false,
                clearSelectionText: '',
              }"
              :pagination-options="{
                enabled: true,
                mode: 'records',
                nextLabel: 'next',
                prevLabel: 'prev',
              }"
            >
              <template slot="table-row" slot-scope="props">
                <div v-if="props.column.field == 'actions'">
                  <a @click="showPayment(props.row)" class="cursor-pointer" v-b-tooltip.hover :title="$t('ShowPayment')">
                    <i class="i-Eye text-25 text-info"></i>
                  </a>
                </div>

                <div v-else-if="props.column.field == 'payment_status'">
                  <span v-if="props.row.payment_status == 'paid'" class="badge badge-outline-success">{{ $t('Paid') }}</span>
                  <span v-else-if="props.row.payment_status == 'partial'" class="badge badge-outline-warning">{{ $t('Partial') }}</span>
                  <span v-else class="badge badge-outline-danger">{{ $t('Unpaid') }}</span>
                </div>

                <span v-else>
                  {{ props.formattedRow[props.column.field] }}
                </span>
              </template>
            </vue-good-table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import jsPDF from "jspdf";
import "jspdf-autotable";
import moment from "moment";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import DateRangePicker from 'vue2-daterange-picker';
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css';
import { mapGetters } from "vuex";

export default {
  components: {
    DateRangePicker
  },
  data() {
    return {
      dateRange: {
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month')
      },
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      totalRows: "",
      search: "",
      Filter_type: "",
      Filter_status: "",
      payments: [],
      limit: "10",
      isLoading: false
    };
  },
  computed: {
    ...mapGetters(["currentUser"]),
    columns() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PaymentType"),
          field: "payment_type",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Product"),
          field: "product_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Amount"),
          field: "montant",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Action"),
          field: "actions",
          html: true,
          tdClass: "text-right",
          thClass: "text-right",
          sortable: false
        }
      ];
    }
  },
  methods: {
    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_All_Payments();
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_All_Payments();
      }
    },

    //---- Event on Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params.type,
          field: params.field
        }
      });
      this.Get_All_Payments();
    },

    //---- Event on Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_All_Payments();
    },

    //---- Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_type = "";
      this.Filter_status = "";
      this.dateRange.startDate = moment().startOf('month');
      this.dateRange.endDate = moment().endOf('month');
      this.Get_All_Payments();
    },

    //------------------------ Payments PDF ------------------
    Payment_PDF() {
      let self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Date", dataKey: "date" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Type", dataKey: "payment_type" },
        { title: "Amount", dataKey: "montant" },
        { title: "Status", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.payments);
      pdf.text("Payment List", 40, 25);
      pdf.save("Payment_List.pdf");
    },

    //-------------------------------- Get All Payments ----------------------
    Get_All_Payments() {
      this.isLoading = true;
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "report/all_payments?page=" +
          this.serverParams.page +
          "&SortField=" +
          this.serverParams.sort.field +
          "&SortType=" +
          this.serverParams.sort.type +
          "&search=" +
          this.search +
          "&limit=" +
          this.limit +
          "&payment_type=" +
          this.Filter_type +
          "&payment_status=" +
          this.Filter_status +
          "&start_date=" +
          moment(this.dateRange.startDate).format('YYYY-MM-DD') +
          "&end_date=" +
          moment(this.dateRange.endDate).format('YYYY-MM-DD')
        )
        .then(response => {
          this.payments = response.data.payments;
          this.totalRows = response.data.totalRows;
          this.isLoading = false;
          NProgress.done();
        })
        .catch(error => {
          this.isLoading = false;
          NProgress.done();
          this.$toast.error(error.response.data.message || 'Error fetching payment data');
        });
    },

    //----------------------------------- Show Payment Details -------------------------------
    showPayment(payment) {
      // Implement payment details modal or navigation
      this.$router.push({
        name: payment.payment_type === 'sale' ? 'detail_sale' : 'detail_purchase',
        params: { id: payment.id }
      });
    }
  },

  //----------------------------- Created function-------------------
  created() {
    this.Get_All_Payments();
  }
};
</script>
