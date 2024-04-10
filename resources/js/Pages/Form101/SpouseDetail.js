import { useTranslation } from "react-i18next";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import SelectElement from "./inputElements/SelectElement";
import TextField from "./inputElements/TextField";

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
                            label="First Name"
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
                            label="Last Name"
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
                    <div className="col-4">
                        <label className="d-block">Identify by *</label>
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
                            ID Number
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
                            Passport
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
                            <div className="col-4">
                                <SelectElement
                                    name={"Spouse.Country"}
                                    label={"Country"}
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
                                    options={[{ label: "abc", value: "xyz" }]}
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="Spouse.passportNumber"
                                    label="Passport Number"
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
                        <div className="col-4">
                            <TextField
                                name="Spouse.IdNumber"
                                label="ID Number"
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
                    <div className="col-4">
                        <DateField
                            name="Spouse.Dob"
                            label="Date of birth"
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
                        <div className="col-4">
                            <DateField
                                name="Spouse.DateOFAliyah"
                                label="Date of Aliyah"
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
                    <div className="col-4">
                        <RadioButtonGroup
                            name="Spouse.hasIncome"
                            label="Incomes of my spouse:"
                            options={[
                                {
                                    label: "My spouse has no income",
                                    value: "No",
                                },
                                {
                                    label: "My spouse has an income",
                                    value: "Yes",
                                },
                            ]}
                            value={values.Spouse.hasIncome}
                            onChange={(e) => {
                                handleChange(e);
                                setFieldValue("Spouse.incomeType", "");
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
                            <RadioButtonGroup
                                name="Spouse.incomeType"
                                label="Income types:"
                                options={[
                                    {
                                        label: "Work/Allowance/Business",
                                        value: "Work/Allowance/Business",
                                    },
                                    { label: "Other", value: "Other" },
                                ]}
                                value={values.Spouse.incomeType}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.Spouse &&
                                    errors.Spouse &&
                                    touched.Spouse.incomeType &&
                                    errors.Spouse.incomeType
                                        ? errors.Spouse.incomeType
                                        : ""
                                }
                                required
                            />
                        )}
                    </div>
                </div>
            ) : (
                <div className="bg-yellow rounded p-2">
                    This section is only relevant if you're married.
                </div>
            )}
        </div>
    );
}
