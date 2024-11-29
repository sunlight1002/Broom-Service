import { useTranslation } from "react-i18next";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import SelectElement from "./inputElements/SelectElement";
import TextField from "./inputElements/TextField";
import CheckBox from "./inputElements/CheckBox";
import { countryOption } from "./cityCountry";

export default function SpouseDetail({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) {
    const { t } = useTranslation();
    return (
        <div>
            <h2>{t("form101.DetailsOfSpouse")}</h2>
            {values.employeeMaritalStatus === "Married" ? (
                <div className="row">
                    <div className="col-6">
                        <TextField
                            name="Spouse.firstName"
                            label={t("form101.label_firstName")}
                            value={values.Spouse.firstName}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.Spouse &&
                                    errors.Spouse &&
                                    touched.Spouse.firstName &&
                                    errors.Spouse.firstName
                                    ? errors.Spouse.firstName
                                    : ""
                            }
                            required
                        />
                    </div>
                    <div className="col-6">
                        <TextField
                            name="Spouse.lastName"
                            label={t("form101.label_lastName")}
                            value={values.Spouse.lastName}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.Spouse &&
                                    errors.Spouse &&
                                    touched.Spouse.lastName &&
                                    errors.Spouse.lastName
                                    ? errors.Spouse.lastName
                                    : ""
                            }
                            required
                        />
                    </div>
                    <div className="col-lg-4">
                        <label className="d-block">{t("form101.idBy")} *</label>
                        <label className="mr-3 ">
                            <input
                                type="radio"
                                name="Spouse.Identity"
                                className="mr-2"
                                value="IDNumber"
                                checked={values.Spouse.Identity === "IDNumber"}
                                onChange={(e) => {
                                    handleChange(e);
                                    setFieldValue("Spouse.Country", "");
                                    setFieldValue("Spouse.passportNumber", "");
                                    setFieldValue("Spouse.IdNumber", "");
                                }}
                            />
                            {t("form101.id_num")}
                        </label>
                        <label>
                            <input
                                type="radio"
                                name="Spouse.Identity"
                                className="mr-2"
                                value="Passport"
                                checked={values.Spouse.Identity === "Passport"}
                                onChange={(e) => {
                                    handleChange(e);
                                    setFieldValue("Spouse.Country", "");
                                    setFieldValue("Spouse.passportNumber", "");
                                    setFieldValue("Spouse.IdNumber", "");
                                }}
                            />
                            {t("form101.Passport")}
                        </label>
                        {touched.Spouse &&
                            errors.Spouse &&
                            touched.Spouse.Identity &&
                            errors.Spouse.Identity && (
                                <p className="text-danger">
                                    {errors.Spouse.Identity}
                                </p>
                            )}
                    </div>

                    {values.Spouse.Identity === "Passport" ? (
                        <>
                            <div className="col-lg-4 col-sm-6 col-12">
                                <SelectElement
                                    name={"Spouse.Country"}
                                    label={t("form101.country_passport")}
                                    value={values.Spouse.Country}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.Spouse &&
                                            errors.Spouse &&
                                            touched.Spouse.Country &&
                                            errors.Spouse.Country
                                            ? errors.Spouse.Country
                                            : ""
                                    }
                                    options={countryOption}
                                />
                            </div>
                            <div className="col-lg-4 col-sm-6 col-12">
                                <TextField
                                    name="Spouse.passportNumber"
                                    label={t("form101.passport_num")}
                                    value={values.Spouse.passportNumber}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.Spouse &&
                                            errors.Spouse &&
                                            touched.Spouse.passportNumber &&
                                            errors.Spouse.passportNumber
                                            ? errors.Spouse.passportNumber
                                            : ""
                                    }
                                    required
                                />
                            </div>
                        </>
                    ) : (
                        <div className="col-lg-4 col-sm-6 col-12">
                            <TextField
                                name="Spouse.IdNumber"
                                label={t("form101.id_num")}
                                value={values.Spouse.IdNumber}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.Spouse &&
                                        errors.Spouse &&
                                        touched.Spouse.IdNumber &&
                                        errors.Spouse.IdNumber
                                        ? errors.Spouse.IdNumber
                                        : ""
                                }
                                required
                            />
                        </div>
                    )}
                    <div className="col-lg-4 col-sm-6 col-12">
                        <DateField
                            name="Spouse.Dob"
                            label={t("form101.dob")}
                            value={values.Spouse.Dob}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.Spouse &&
                                    errors.Spouse &&
                                    touched.Spouse.Dob &&
                                    errors.Spouse.Dob
                                    ? errors.Spouse.Dob
                                    : ""
                            }
                            required
                        />
                    </div>
                    {values.Spouse.Identity === "IDNumber" && (
                        <div className="col-lg-4 col-sm-6 col-12">
                            <DateField
                                name="Spouse.DateOFAliyah"
                                label={t("form101.dom")}
                                value={values.Spouse.DateOFAliyah}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.Spouse &&
                                        errors.Spouse &&
                                        touched.Spouse.DateOFAliyah &&
                                        errors.Spouse.DateOFAliyah
                                        ? errors.Spouse.DateOFAliyah
                                        : ""
                                }
                            />
                        </div>
                    )}
                    <div className="col-lg-4 col-sm-6 col-12">
                        <RadioButtonGroup
                            name="Spouse.hasIncome"
                            label={t("form101.incomeSpouse")}
                            options={[
                                {
                                    label: t("form101.NoIncomeSpouse"),
                                    value: "No",
                                },
                                {
                                    label: t("form101.hasIncomeSpouse"),
                                    value: "Yes",
                                },
                            ]}
                            value={values.Spouse.hasIncome}
                            onChange={(e) => {
                                handleChange(e);
                            }}
                            onBlur={handleBlur}
                            error={
                                touched.Spouse &&
                                    errors.Spouse &&
                                    touched.Spouse.hasIncome &&
                                    errors.Spouse.hasIncome
                                    ? errors.Spouse.hasIncome
                                    : ""
                            }
                            required
                        />
                        {values.Spouse.hasIncome === "Yes" && (
                            <>
                                <label htmlFor="label" className="form-label">
                                    {t("form101.IncomeTypes")} *
                                </label>
                                <div className="row">
                                    <div className="col-12">
                                        <CheckBox
                                            name={"Spouse.incomeTypeOpt1"}
                                            label={t("form101.incomeTypeOpt1")}
                                            value={values.Spouse.incomeTypeOpt1}
                                            checked={
                                                values.Spouse.incomeTypeOpt1
                                            }
                                            onChange={(e) =>
                                                setFieldValue(
                                                    "Spouse.incomeTypeOpt1",
                                                    e.target.checked
                                                )
                                            }
                                            onBlur={handleBlur}
                                            error={
                                                touched.Spouse &&
                                                    errors.Spouse &&
                                                    touched.Spouse.incomeTypeOpt1 &&
                                                    errors.Spouse.incomeTypeOpt1
                                                    ? errors.Spouse
                                                        .incomeTypeOpt1
                                                    : ""
                                            }
                                        />
                                    </div>
                                    <div className="col-12">
                                        <CheckBox
                                            name={"Spouse.incomeTypeOpt2"}
                                            label={t("form101.incomeTypeOpt2")}
                                            value={values.Spouse.incomeTypeOpt2}
                                            onChange={(e) => {
                                                setFieldValue(
                                                    "Spouse.incomeTypeOpt2",
                                                    e.target.checked
                                                );
                                                handleChange(e);
                                            }
                                            }
                                            onBlur={handleBlur}
                                            error={
                                                touched.Spouse &&
                                                    errors.Spouse &&
                                                    touched.Spouse.incomeTypeOpt2 &&
                                                    errors.Spouse.incomeTypeOpt2
                                                    ? errors.Spouse
                                                        .incomeTypeOpt2
                                                    : ""
                                            }
                                        />
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            ) : (
                <div className="bg-yellow rounded p-2">
                    {t("form101.spouseWarning")}
                </div>
            )}
        </div>
    );
}
