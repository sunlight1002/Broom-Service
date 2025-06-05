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
    handleBubbleToggle,
    activeBubble
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
                            toggleBubble={handleBubbleToggle} // Pass the toggle handler
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
                        {activeBubble === 'Spouse.firstName' && (
                            <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                <div className="speech up">
                                    {t("Enter your spouse first name.")}
                                </div>
                            </div>
                        )}   
                    </div>
                    <div className="col-6">
                        <TextField
                            name="Spouse.lastName"
                            label={t("form101.label_lastName")}
                            value={values.Spouse.lastName}
                            onChange={handleChange}
                            toggleBubble={handleBubbleToggle} // Pass the toggle handler
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
                        {activeBubble === 'Spouse.lastName' && (
                            <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                <div className="speech up">
                                    {t("Enter your spouse last name.")}
                                </div>
                            </div>
                        )}   
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
                                    toggleBubble={handleBubbleToggle} // Pass the toggle handler
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
                                {activeBubble === 'Spouse.passportNumber' && (
                                    <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                        <div className="speech up">
                                            {t("Enter your spouse passport number.")}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </>
                    ) : (
                        <div className="col-lg-4 col-sm-6 col-12">
                            <TextField
                                name="Spouse.IdNumber"
                                label={t("form101.id_num")}
                                value={values.Spouse.IdNumber}
                                onChange={handleChange}
                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
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
                            {activeBubble === 'Spouse.IdNumber' && (
                                <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                    <div className="speech up">
                                        {t("Enter your spouse identity number.")}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="col-lg-4 col-sm-6 col-12">
                        <div style={{ width: "calc(100% - 46px)"}}>
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
                            <div
                                className="position-absolute"
                                style={{
                                    right: "15px",
                                    top: "60%",
                                    transform: "translateY(-50%)",
                                    cursor: "pointer",
                                    color: "#000",
                                    backgroundColor: "#fafafa",
                                    borderRadius: "25%", 
                                    padding: "7px 8px 5px 8px",
                                    zIndex: 2
                                }}
                                onClick={() => {handleBubbleToggle("Spouse.Dob")}}
                                title="Click for help"
                                >
                                <svg
                                    stroke="currentColor"
                                    fill="currentColor"
                                    strokeWidth="0"
                                    viewBox="0 0 1024 1024"
                                    height="1.5em"
                                    width="1.4em"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path>
                                    <path d="M623.6 316.7C593.6 290.4 554 276 512 276s-81.6 14.5-111.6 40.7C369.2 344 352 380.7 352 420v7.6c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V420c0-44.1 43.1-80 96-80s96 35.9 96 80c0 31.1-22 59.6-56.1 72.7-21.2 8.1-39.2 22.3-52.1 40.9-13.1 19-19.9 41.8-19.9 64.9V620c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8v-22.7a48.3 48.3 0 0 1 30.9-44.8c59-22.7 97.1-74.7 97.1-132.5.1-39.3-17.1-76-48.3-103.3zM472 732a40 40 0 1 0 80 0 40 40 0 1 0-80 0z"></path>
                                </svg>
                            </div>

                            {activeBubble === "Spouse.Dob" && (
                                <div className="position-absolute speechand up">
                                    {t("Enter your date of birth in MM/DD/YYYY format.")}
                                </div>
                            )}       
                        </div>
                    </div>
                    {values.Spouse.Identity === "IDNumber" && (
                        <div className="col-lg-4 col-sm-6 col-12">
                            <div style={{ width: "calc(100% - 46px)"}}>
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
                            <div
                                className="position-absolute"
                                style={{
                                    right: "15px",
                                    top: "60%",
                                    transform: "translateY(-50%)",
                                    cursor: "pointer",
                                    color: "#000",
                                    backgroundColor: "#fafafa",
                                    borderRadius: "25%", 
                                    padding: "7px 8px 5px 8px",
                                    zIndex: 2
                                }}
                                onClick={() => {handleBubbleToggle("Spouse.DateOFAliyah")}}
                                title="Click for help"
                                >
                                <svg
                                    stroke="currentColor"
                                    fill="currentColor"
                                    strokeWidth="0"
                                    viewBox="0 0 1024 1024"
                                    height="1.5em"
                                    width="1.4em"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path>
                                    <path d="M623.6 316.7C593.6 290.4 554 276 512 276s-81.6 14.5-111.6 40.7C369.2 344 352 380.7 352 420v7.6c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V420c0-44.1 43.1-80 96-80s96 35.9 96 80c0 31.1-22 59.6-56.1 72.7-21.2 8.1-39.2 22.3-52.1 40.9-13.1 19-19.9 41.8-19.9 64.9V620c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8v-22.7a48.3 48.3 0 0 1 30.9-44.8c59-22.7 97.1-74.7 97.1-132.5.1-39.3-17.1-76-48.3-103.3zM472 732a40 40 0 1 0 80 0 40 40 0 1 0-80 0z"></path>
                                </svg>
                            </div>

                            {activeBubble === "Spouse.DateOFAliyah" && (
                                <div className="position-absolute speechand up">
                                    {t("Enter date of immigration.")}
                                </div>
                            )} 
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
